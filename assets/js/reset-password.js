(function(){
  'use strict';

  function qs(name) {
    const url = new URL(window.location.href);
    return url.searchParams.get(name);
  }

  function showError(msg) {
    document.getElementById('reset-error').textContent = msg;
  }

  async function submitReset(token, newPassword) {
    const res = await fetch('/admin/api/complete-password-reset.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token, newPassword })
    });
    const json = await res.json();
    if (!res.ok) throw new Error(json.message || 'Reset failed');
    return json;
  }

  document.addEventListener('DOMContentLoaded', function(){
    const token = qs('token');
    if (!token) {
      document.getElementById('reset-note').textContent = 'Invalid reset link. Please request another password reset.';
    }

    const p1 = document.getElementById('new-password');
    const p2 = document.getElementById('confirm-password');
    const submit = document.getElementById('reset-submit');
    const strengthBar = document.getElementById('strength-bar');

    p1.addEventListener('input', function(){
      const v = this.value || '';
      const validation = PasswordManager.validateStrength(v);
      strengthBar.style.width = validation.strength + '%';
      if (validation.strength < 40) strengthBar.style.background = '#ef4444';
      else if (validation.strength < 70) strengthBar.style.background = '#f59e0b';
      else strengthBar.style.background = '#10b981';
    });

    submit.addEventListener('click', async function(){
      showError('');
      const v1 = p1.value || '';
      const v2 = p2.value || '';
      if (v1 !== v2) { showError('Passwords do not match'); return; }
      const validation = PasswordManager.validateStrength(v1);
      if (!validation.isValid) { showError(validation.errors.join(', ')); return; }
      submit.disabled = true;
      submit.textContent = 'Updating...';
      try {
        const result = await submitReset(token, v1);
        // Fetch whoami to get current user and csrf token
        const who = await fetch('/admin/api/whoami.php');
        const whoJson = await who.json();
        if (who.ok && whoJson.success && whoJson.user) {
          // create session client-side for UI
          SessionManager.createSession({ id: whoJson.user.id || 'user-001', username: whoJson.user.username || '', email: whoJson.user.email || '', role: whoJson.user.role || 'admin' });
          // expose csrf token
          window.CaliusCMS = window.CaliusCMS || {};
          window.CaliusCMS.auth = { csrfToken: whoJson.csrfToken || null, currentUser: whoJson.user };
        }
        window.location.href = '/admin/index.html';
      } catch (e) {
        showError(e.message || 'Failed to reset password');
        submit.disabled = false;
        submit.textContent = 'Set New Password';
      }
    });
  });

})();
