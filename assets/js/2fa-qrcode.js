/* ============================================
   CALIUS DIGITAL - 2FA QR Code Generator
   Simple 2FA implementation with QR Code
   ============================================ */

// Simple QR Code generator (inline implementation)
let current2FASecret = '';

// Generate random secret key
function generateSecret() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 characters
    let secret = '';
    for (let i = 0; i < 32; i++) {
        secret += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return secret;
}

// Generate 2FA QR Code
async function generate2FACode() {
    try {
        // Request server to generate secret + QR
        const resp = await fetch('/admin/api/setup-2fa.php', { method: 'POST' });
        const result = await resp.json();
        if (!resp.ok) {
            alert('Failed to generate 2FA code: ' + (result.message || resp.statusText));
            return;
        }

        current2FASecret = result.secret;
        // Show QR code container
        document.getElementById('qr-code-container').style.display = 'block';
        const qrCodeDiv = document.getElementById('qr-code');
        qrCodeDiv.innerHTML = `<img src="${result.qr}" alt="QR Code" style="max-width: 100%; height: auto;">`;
        document.getElementById('manual-key').textContent = current2FASecret;
    } catch (err) {
        console.error('generate2FACode error', err);
        alert('Failed to generate 2FA code. See console.');
    }
}

// Simple TOTP verification (basic implementation)
async function verify2FACode() {
    const code = document.getElementById('2fa-verify-code').value;
    if (!code || code.length !== 6) {
        alert('Please enter a valid 6-digit code');
        return;
    }

    try {
        const resp = await fetch('/admin/api/verify-2fa.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ secret: current2FASecret, token: code })
        });
        const result = await resp.json();
        if (!resp.ok) {
            alert('Verification failed: ' + (result.message || resp.statusText));
            return;
        }

        CaliusCMS.showNotification('2FA enabled successfully', 'success');
        // Hide the QR container
        document.getElementById('qr-code-container').style.display = 'none';
    } catch (err) {
        console.error('verify2FACode error', err);
        alert('Verification failed. See console.');
    }
}

// Show 2FA save instructions
function show2FASaveInstructions(secret) {
    const usersData = {
        "users": [
            {
                "id": "user-001",
                "username": "admin",
                "email": "admin@calius.digital",
                "password": "[KEEP_EXISTING_PASSWORD_HASH]",
                "role": "admin",
                "firstName": "Admin",
                "lastName": "Calius",
                "avatar": "/assets/images/avatars/admin.jpg",
                "twoFactorEnabled": true,
                "twoFactorSecret": secret,
                "createdAt": "2024-01-01T00:00:00Z",
                "lastLogin": null,
                "loginAttempts": 0,
                "lockedUntil": null,
                "status": "active",
                "permissions": [
                    "manage_templates",
                    "manage_blog",
                    "manage_orders",
                    "manage_users",
                    "manage_settings",
                    "view_analytics"
                ]
            }
        ],
        "sessions": [],
        "loginHistory": []
    };
    
    const jsonString = JSON.stringify(usersData, null, 2);
    
    const modal = document.createElement('div');
    modal.className = 'admin-modal-overlay active';
    modal.innerHTML = `
        <div class="admin-modal" style="max-width: 700px;">
            <div class="admin-modal-header">
                <h3 class="admin-modal-title">🔐 Save 2FA Configuration</h3>
                <button class="admin-modal-close" onclick="this.closest('.admin-modal-overlay').remove()">×</button>
            </div>
            <div class="admin-modal-body">
                <div class="admin-alert warning" style="margin-bottom: 1.5rem;">
                    <div class="admin-alert-icon">⚠️</div>
                    <div class="admin-alert-content">
                        <div class="admin-alert-title">IMPORTANT: 2FA Setup Instructions</div>
                        <p>Follow these steps to enable 2FA:</p>
                    </div>
                </div>
                
                <div style="background: var(--bg-secondary); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <h4 style="margin-bottom: 0.5rem;">📝 Steps to Enable 2FA:</h4>
                    <ol style="margin-left: 1.5rem; line-height: 1.8;">
                        <li>Copy the JSON below</li>
                        <li>Login to cPanel File Manager</li>
                        <li>Navigate to <code>/data/users.json</code></li>
                        <li>Edit the file</li>
                        <li><strong>IMPORTANT:</strong> Keep your existing password hash!</li>
                        <li>Update "twoFactorEnabled" to <code>true</code></li>
                        <li>Update "twoFactorSecret" to <code>${secret}</code></li>
                        <li>Save the file</li>
                    </ol>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <strong>Your 2FA Secret Key:</strong>
                    <code style="display: block; padding: 0.5rem; background: var(--bg-secondary); border-radius: 0.25rem; margin-top: 0.5rem; word-break: break-all;">${secret}</code>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <strong>File to update:</strong> <code style="background: var(--bg-secondary); padding: 0.25rem 0.5rem; border-radius: 0.25rem;">/data/users.json</code>
                </div>
                
                <textarea class="admin-form-textarea" rows="20" readonly style="font-family: monospace; font-size: 0.875rem;">${jsonString}</textarea>
                
                <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;" onclick="navigator.clipboard.writeText('${secret}'); alert('✓ Secret key copied to clipboard!')">
                    📋 Copy Secret Key Only
                </button>
                
                <div class="admin-alert error" style="margin-top: 1rem;">
                    <div class="admin-alert-icon">⚠️</div>
                    <div class="admin-alert-content">
                        <div class="admin-alert-title">Keep Your Secret Safe!</div>
                        <p>Save this secret key in a secure location. You'll need it if you lose access to your authenticator app.</p>
                        <p style="margin-top: 0.5rem;"><strong>Secret: ${secret}</strong></p>
                    </div>
                </div>
            </div>
            <div class="admin-modal-footer">
                <button class="btn btn-secondary" onclick="this.closest('.admin-modal-overlay').remove()">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

console.log('2FA QR Code Generator loaded');
