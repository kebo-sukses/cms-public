/* ============================================
   CALIUS DIGITAL - Simple Authentication
   Simplified login without external dependencies
   ============================================ */

(function() {
  'use strict';

  // DEPRECATED: simple-auth.js has been replaced by auth.js
  // Keep this file only for backwards compatibility; do not register globals.
  console.warn('simple-auth.js is deprecated. Use /assets/js/auth.js instead.');
  return;

  // Load credentials from users.json
  let VALID_CREDENTIALS = null;
  
  // Load user data
  async function loadUserCredentials() {
    try {
      const response = await fetch('/data/users.json');
      const data = await response.json();
      if (data.users && data.users.length > 0) {
        const user = data.users[0];
        VALID_CREDENTIALS = {
          username: user.username,
          email: user.email,
          password: user.password,
          role: user.role || 'admin',
          twoFactorEnabled: user.twoFactorEnabled || false
        };
        console.log('User credentials loaded successfully');
        return true;
      }
    } catch (error) {
      console.error('Failed to load user credentials:', error);
      // Fallback to default
      VALID_CREDENTIALS = {
        username: 'admin',
        email: 'admin@calius.digital',
        password: '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9',
        role: 'admin',
        twoFactorEnabled: false
      };
    }
    return false;
  }

  // Simple SHA-256 hash function
  async function hashPassword(password) {
    const encoder = new TextEncoder();
    const data = encoder.encode(password);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
  }

  // Create session
  function createSession(username) {
    const session = {
      username: username,
      role: 'admin',
      loginTime: Date.now(),
      expiresAt: Date.now() + (3600000) // 1 hour
    };
    sessionStorage.setItem('calius_session', JSON.stringify(session));
    return session;
  }

  // Check session
  function checkSession() {
    const sessionData = sessionStorage.getItem('calius_session');
    if (!sessionData) return null;
    
    const session = JSON.parse(sessionData);
    if (Date.now() > session.expiresAt) {
      sessionStorage.removeItem('calius_session');
      return null;
    }
    
    return session;
  }

  // Initialize login form
  async function initLoginForm() {
    const loginForm = document.getElementById('login-form');
    if (!loginForm) return;

    console.log('Login form initialized');
    
    // Load credentials first
    await loadUserCredentials();

    loginForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      console.log('Form submitted');
      
      // Reload credentials before each login attempt
      await loadUserCredentials();

      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      const submitBtn = loginForm.querySelector('button[type="submit"]');
      const errorDiv = document.getElementById('login-error');

      try {
        // Show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading"></span> Logging in...';
        errorDiv.textContent = '';
        errorDiv.classList.remove('show');

        console.log('Attempting login for:', username);

        // Hash the entered password
        const passwordHash = await hashPassword(password);
        console.log('Password hash:', passwordHash);
        console.log('Expected hash:', VALID_CREDENTIALS.password);

        // Check credentials
        const isValidUsername = (username === VALID_CREDENTIALS.username || username === VALID_CREDENTIALS.email);
        const isValidPassword = (passwordHash === VALID_CREDENTIALS.password);

        console.log('Username valid:', isValidUsername);
        console.log('Password valid:', isValidPassword);

        if (!isValidUsername || !isValidPassword) {
          throw new Error('Invalid username or password');
        }

        // Success - create session
        console.log('Login successful!');
        createSession(username);

        // Show success message
        errorDiv.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
        errorDiv.style.color = '#10b981';
        errorDiv.textContent = '✓ Login successful! Redirecting...';
        errorDiv.classList.add('show');

        // Redirect to dashboard
        setTimeout(() => {
          window.location.href = '/admin/index.html';
        }, 1000);

      } catch (error) {
        console.error('Login error:', error);
        errorDiv.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
        errorDiv.style.color = '#ef4444';
        errorDiv.textContent = error.message;
        errorDiv.classList.add('show');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Login';
      }
    });
  }

  // Check authentication for admin pages
  function requireAuth() {
    const session = checkSession();
    
    if (!session) {
      if (!window.location.pathname.includes('login.html')) {
        console.log('No session, redirecting to login');
        window.location.href = '/admin/login.html';
      }
      return false;
    }
    
    console.log('Session valid:', session);
    return true;
  }

  // Logout function
  function logout() {
    sessionStorage.removeItem('calius_session');
    window.location.href = '/admin/login.html';
  }

  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
    console.log('Simple Auth loaded');
    console.log('Current path:', window.location.pathname);

    // Initialize login form if on login page
    if (window.location.pathname.includes('login.html')) {
      initLoginForm();
    }

    // Check authentication for admin pages
    if (window.location.pathname.includes('/admin/') && !window.location.pathname.includes('login.html')) {
      requireAuth();
    }
  });

  // Export to global scope
  window.CaliusAuth = {
    logout: logout,
    requireAuth: requireAuth,
    checkSession: checkSession
  };

  console.log('Calius Simple Authentication initialized');

})();
