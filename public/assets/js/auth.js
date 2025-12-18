/* ============================================
   CALIUS DIGITAL - Authentication System
   Login with Password + Google Authenticator 2FA
   ============================================ */

(function() {
  'use strict';

  // ============================================
  // Authentication State
  // ============================================
  
  const authState = {
    currentUser: null,
    isAuthenticated: false,
    sessionToken: null,
    loginAttempts: 0,
    maxAttempts: 5,
    lockoutDuration: 15 * 60 * 1000 // 15 minutes
  };

  // ============================================
  // Session Management
  // ============================================
  
  class SessionManager {
    static SESSION_KEY = 'calius_session';
    static SESSION_DURATION = 3600000; // 1 hour

    static createSession(user) {
      const session = {
        userId: user.id,
        username: user.username,
        email: user.email,
        role: user.role,
        token: this.generateToken(),
        createdAt: Date.now(),
        expiresAt: Date.now() + this.SESSION_DURATION
      };

      sessionStorage.setItem(this.SESSION_KEY, JSON.stringify(session));
      authState.sessionToken = session.token;
      authState.currentUser = user;
      authState.isAuthenticated = true;

      return session;
    }

    static getSession() {
      const sessionData = sessionStorage.getItem(this.SESSION_KEY);
      if (!sessionData) return null;

      const session = JSON.parse(sessionData);
      
      // Check if session expired
      if (Date.now() > session.expiresAt) {
        this.destroySession();
        return null;
      }

      return session;
    }

    static destroySession() {
      sessionStorage.removeItem(this.SESSION_KEY);
      authState.sessionToken = null;
      authState.currentUser = null;
      authState.isAuthenticated = false;
    }

    static refreshSession() {
      const session = this.getSession();
      if (session) {
        session.expiresAt = Date.now() + this.SESSION_DURATION;
        sessionStorage.setItem(this.SESSION_KEY, JSON.stringify(session));
      }
    }

    static generateToken() {
      return Array.from(crypto.getRandomValues(new Uint8Array(32)))
        .map(b => b.toString(16).padStart(2, '0'))
        .join('');
    }
  }

  // ============================================
  // Password Hashing (bcrypt simulation)
  // ============================================
  
  class PasswordManager {
    static async hash(password) {
      // In production, use proper bcrypt library
      // This is a simplified version for demonstration
      const encoder = new TextEncoder();
      const data = encoder.encode(password);
      const hashBuffer = await crypto.subtle.digest('SHA-256', data);
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }

    static async verify(password, hash) {
      const passwordHash = await this.hash(password);
      return passwordHash === hash;
    }

    static validateStrength(password) {
      const minLength = 8;
      const hasUpperCase = /[A-Z]/.test(password);
      const hasLowerCase = /[a-z]/.test(password);
      const hasNumbers = /\d/.test(password);
      const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

      const errors = [];
      
      if (password.length < minLength) {
        errors.push(`Password must be at least ${minLength} characters long`);
      }
      if (!hasUpperCase) {
        errors.push('Password must contain at least one uppercase letter');
      }
      if (!hasLowerCase) {
        errors.push('Password must contain at least one lowercase letter');
      }
      if (!hasNumbers) {
        errors.push('Password must contain at least one number');
      }
      if (!hasSpecialChar) {
        errors.push('Password must contain at least one special character');
      }

      return {
        isValid: errors.length === 0,
        errors,
        strength: this.calculateStrength(password)
      };
    }

    static calculateStrength(password) {
      let strength = 0;
      if (password.length >= 8) strength += 20;
      if (password.length >= 12) strength += 20;
      if (/[a-z]/.test(password)) strength += 15;
      if (/[A-Z]/.test(password)) strength += 15;
      if (/\d/.test(password)) strength += 15;
      if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 15;
      
      return Math.min(strength, 100);
    }
  }

  // ============================================
  // Two-Factor Authentication (Google Authenticator)
  // ============================================
  
  class TwoFactorAuth {
    static generateSecret() {
      // Generate base32 secret for Google Authenticator
      const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
      let secret = '';
      for (let i = 0; i < 32; i++) {
        secret += chars[Math.floor(Math.random() * chars.length)];
      }
      return secret;
    }

    static generateQRCode(username, secret) {
      const issuer = 'Calius Digital';
      const otpauthUrl = `otpauth://totp/${encodeURIComponent(issuer)}:${encodeURIComponent(username)}?secret=${secret}&issuer=${encodeURIComponent(issuer)}`;
      
      // Use QR code API (you can use any QR code library)
      return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(otpauthUrl)}`;
    }

    static verifyToken(secret, token) {
        // Implement TOTP verification (RFC 6238)
        // Accepts base32 secret string and numeric token string
        try {
          if (!secret || !/^\d{6}$/.test(token)) return false;

          // Helper: base32 decode
          function base32Decode(str) {
            const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
            const cleaned = str.toUpperCase().replace(/=+$/, '');
            const bytes = [];
            let bits = 0;
            let value = 0;
            for (let i = 0; i < cleaned.length; i++) {
              const idx = alphabet.indexOf(cleaned.charAt(i));
              if (idx === -1) continue;
              value = (value << 5) | idx;
              bits += 5;
              if (bits >= 8) {
                bytes.push((value >>> (bits - 8)) & 0xff);
                bits -= 8;
              }
            }
            return new Uint8Array(bytes);
          }

          // Compute TOTP for a given counter
          async function totpAtCounter(keyBytes, counter) {
            // counter -> 8-byte big-endian
            const buf = new ArrayBuffer(8);
            const view = new DataView(buf);
            // High 32 bits
            const high = Math.floor(counter / 0x100000000);
            const low = counter & 0xffffffff;
            view.setUint32(0, high);
            view.setUint32(4, low);

            const cryptoKey = await crypto.subtle.importKey('raw', keyBytes, { name: 'HMAC', hash: 'SHA-1' }, false, ['sign']);
            const sig = new Uint8Array(await crypto.subtle.sign('HMAC', cryptoKey, buf));

            const offset = sig[sig.length - 1] & 0x0f;
            const code = ((sig[offset] & 0x7f) << 24) | ((sig[offset + 1] & 0xff) << 16) | ((sig[offset + 2] & 0xff) << 8) | (sig[offset + 3] & 0xff);
            const otp = (code % 1000000).toString().padStart(6, '0');
            return otp;
          }

          const key = base32Decode(secret);
          const timestep = 30;
          const now = Math.floor(Date.now() / 1000);
          const counter = Math.floor(now / timestep);

          // Allow a small window of -1..+1 steps
          for (let i = -1; i <= 1; i++) {
            const expected = await totpAtCounter(key, counter + i);
            if (expected === token) return true;
          }

          return false;
        } catch (e) {
          console.error('TOTP verification error', e);
          return false;
        }
    }

    static async setup(username) {
      const secret = this.generateSecret();
      const qrCodeUrl = this.generateQRCode(username, secret);

      return {
        secret,
        qrCodeUrl,
        backupCodes: this.generateBackupCodes()
      };
    }

    static generateBackupCodes(count = 10) {
      const codes = [];
      for (let i = 0; i < count; i++) {
        const code = Math.random().toString(36).substring(2, 10).toUpperCase();
        codes.push(code);
      }
      return codes;
    }
  }

  // ============================================
  // Login Handler
  // ============================================
  
  class LoginHandler {
    static async login(username, password, totpToken = null) {
      try {
        // Delegate authentication to server
        const resp = await fetch('/admin/api/login.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username, password, totp: totpToken })
        });
        const result = await resp.json();
        if (!resp.ok && !result.requiresTwoFactor) {
          this.recordFailedAttempt();
          throw new Error(result.message || 'Login failed');
        }

        if (result.requiresTwoFactor) {
          return { requiresTwoFactor: true, userId: result.userId };
        }

        if (result.requiresPasswordChange) {
          return { requiresPasswordChange: true, token: result.token, expiresIn: result.expiresIn };
        }

        // Success - create local session for client-side gating and fetch permissions
        this.resetLoginAttempts();
        SessionManager.createSession({ id: 'user-001', username: username, email: username, role: 'admin' });
        // Fetch current user / permissions from server
        try {
          const who = await fetch('/admin/api/whoami.php', { credentials: 'same-origin' });
          const whoJson = await who.json();
          if (who.ok && whoJson.success && whoJson.user) {
            authState.currentUser = whoJson.user;
            // store csrf token for subsequent requests
            authState.csrfToken = whoJson.csrfToken || null;
            // also reflect in session storage
            const session = SessionManager.getSession();
            if (session) { session.csrfToken = authState.csrfToken; sessionStorage.setItem(SessionManager.SESSION_KEY, JSON.stringify(session)); }
            // expose to global for the CMS to pick up
            if (!window.CaliusCMS) window.CaliusCMS = {};
            window.CaliusCMS.auth = { csrfToken: authState.csrfToken, currentUser: authState.currentUser };
          }
        } catch (e) {
          console.warn('whoami fetch failed', e);
        }
        return { success: true };
      } catch (err) {
        throw err;
      }
    }

    static async completePasswordReset(token, newPassword) {
      const resp = await fetch('/admin/api/complete-password-reset.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token, newPassword })
      });
      const json = await resp.json();
      if (!resp.ok) throw new Error(json.message || 'Password reset failed');
      // On success server creates session and returns csrfToken
      SessionManager.createSession({ id: json.userId ?? 'user-001' });
      authState.csrfToken = json.csrfToken || null;
      if (!window.CaliusCMS) window.CaliusCMS = {};
      window.CaliusCMS.auth = { csrfToken: authState.csrfToken, currentUser: authState.currentUser };
      return json;
    }

    static recordFailedAttempt() {
      authState.loginAttempts++;
      localStorage.setItem('login_attempts', authState.loginAttempts.toString());
      
      if (authState.loginAttempts >= authState.maxAttempts) {
        const lockoutUntil = Date.now() + authState.lockoutDuration;
        localStorage.setItem('lockout_until', lockoutUntil.toString());
      }
    }

    static resetLoginAttempts() {
      authState.loginAttempts = 0;
      localStorage.removeItem('login_attempts');
      localStorage.removeItem('lockout_until');
    }

    static isLockedOut() {
      const lockoutUntil = localStorage.getItem('lockout_until');
      if (!lockoutUntil) return false;
      
      const lockoutTime = parseInt(lockoutUntil);
      if (Date.now() > lockoutTime) {
        this.resetLoginAttempts();
        return false;
      }
      
      return true;
    }

    static getRemainingLockoutTime() {
      const lockoutUntil = localStorage.getItem('lockout_until');
      if (!lockoutUntil) return 0;
      
      const remaining = parseInt(lockoutUntil) - Date.now();
      return Math.max(0, remaining);
    }

    static async updateLastLogin(userId) {
      // In production, update via API
      console.log(`Last login updated for user ${userId}`);
    }
  }

  // ============================================
  // Logout Handler
  // ============================================
  
  function logout() {
    // Call server to destroy session
    fetch('/admin/api/logout.php', { method: 'POST' }).finally(() => {
      SessionManager.destroySession();
      window.location.href = '/admin/login.html';
    });
  }

  // ============================================
  // Authentication Check
  // ============================================
  
  function requireAuth() {
    const session = SessionManager.getSession();
    
    if (!session) {
      // Not authenticated, redirect to login
      if (!window.location.pathname.includes('login.html')) {
        window.location.href = '/admin/login.html';
      }
      return false;
    }

    // Refresh session
    SessionManager.refreshSession();
    authState.currentUser = session;
    authState.isAuthenticated = true;

    return true;
  }

  // ============================================
  // Permission Check
  // ============================================
  
  function hasPermission(permission) {
    if (!authState.currentUser) return false;
    if (authState.currentUser.role === 'admin') return true;
    return (authState.currentUser.permissions || []).includes(permission);
  }

  // ============================================
  // Login Form Handler
  // ============================================
  
  function initializeLoginForm() {
    const loginForm = document.getElementById('login-form');
    if (!loginForm) return;

    loginForm.addEventListener('submit', async function(e) {
      e.preventDefault();

      const username = document.getElementById('username').value;
      const password = document.getElementById('password').value;
      const totpToken = document.getElementById('totp-token')?.value;

      const submitBtn = loginForm.querySelector('button[type="submit"]');
      const errorDiv = document.getElementById('login-error');

      try {
        // Disable button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading"></span> Logging in...';
        errorDiv.textContent = '';

        const result = await LoginHandler.login(username, password, totpToken);

        if (result.requiresTwoFactor) {
          // Show 2FA input
          document.getElementById('2fa-section').style.display = 'block';
          document.getElementById('totp-token').focus();
          submitBtn.disabled = false;
          submitBtn.textContent = 'Verify & Login';
          return;
        }

        if (result.requiresPasswordChange) {
          // redirect to dedicated reset page where user can set a new password
          window.location.href = '/admin/reset-password.html?token=' + encodeURIComponent(result.token);
          return;
        }

        if (result.success) {
          // Redirect to dashboard
          window.location.href = '/admin/index.html';
        }

      } catch (error) {
        errorDiv.textContent = error.message;
        submitBtn.disabled = false;
        submitBtn.textContent = 'Login';
      }
    });

    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const strengthIndicator = document.getElementById('password-strength');
    
    if (passwordInput && strengthIndicator) {
      passwordInput.addEventListener('input', function() {
        const validation = PasswordManager.validateStrength(this.value);
        strengthIndicator.style.width = validation.strength + '%';
        
        if (validation.strength < 40) {
          strengthIndicator.style.backgroundColor = '#ef4444';
        } else if (validation.strength < 70) {
          strengthIndicator.style.backgroundColor = '#f59e0b';
        } else {
          strengthIndicator.style.backgroundColor = '#10b981';
        }
      });
    }
  }

  // ============================================
  // 2FA Setup Handler
  // ============================================
  
  async function setup2FA(username) {
    const setup = await TwoFactorAuth.setup(username);
    
    // Display QR code and backup codes
    const modal = document.createElement('div');
    modal.className = 'admin-modal-overlay active';
    modal.innerHTML = `
      <div class="admin-modal">
        <div class="admin-modal-header">
          <h3 class="admin-modal-title">Setup Two-Factor Authentication</h3>
        </div>
        <div class="admin-modal-body">
          <p>Scan this QR code with Google Authenticator app:</p>
          <img src="${setup.qrCodeUrl}" alt="QR Code" style="display: block; margin: 1rem auto;" />
          <p><strong>Secret Key:</strong> <code>${setup.secret}</code></p>
          <hr style="margin: 1.5rem 0;" />
          <p><strong>Backup Codes</strong> (save these in a safe place):</p>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; font-family: monospace;">
            ${setup.backupCodes.map(code => `<div>${code}</div>`).join('')}
          </div>
        </div>
        <div class="admin-modal-footer">
          <button class="btn btn-primary" onclick="this.closest('.admin-modal-overlay').remove()">Done</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);

    return setup;
  }

  // ============================================
  // Initialize on page load
  // ============================================
  
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize login form if on login page
    initializeLoginForm();

    // Check authentication for admin pages
    if (window.location.pathname.includes('/admin/') && !window.location.pathname.includes('login.html')) {
      requireAuth();
    }

    // Load login attempts from storage
    const attempts = localStorage.getItem('login_attempts');
    if (attempts) {
      authState.loginAttempts = parseInt(attempts);
    }
  });

  // ============================================
  // Export Authentication API
  // ============================================
  
  window.CaliusAuth = {
    state: authState,
    SessionManager,
    PasswordManager,
    TwoFactorAuth,
    LoginHandler,
    logout,
    requireAuth,
    hasPermission,
    setup2FA
  };

  console.log('Calius Authentication initialized');

})();
