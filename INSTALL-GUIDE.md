# 🚀 Panduan Instalasi Cepat - Calius Digital

## ✅ Checklist Instalasi

### 1. Upload Files ke cPanel
- [ ] Login ke cPanel
- [ ] Buka File Manager
- [ ] Navigate ke `public_html/`
- [ ] Upload SEMUA file ke `public_html/` (BUKAN ke subfolder)
- [ ] Pastikan struktur seperti ini:
  ```
  public_html/
  ├── index.html
  ├── .htaccess
  ├── admin/
  ├── assets/
  ├── data/
  └── ...
  ```

### 2. Set File Permissions
```
Folders: 755
Files: 644
```

### 3. Upload File users.json yang Sudah Diupdate
⚠️ **PENTING:** File `data/users.json` harus ter-upload dengan password hash yang benar!

Via cPanel File Manager:
1. Navigate ke `public_html/data/`
2. Upload file `users.json` yang baru
3. Atau edit langsung dan paste content ini:

```json
{
  "users": [
    {
      "id": "user-001",
      "username": "admin",
      "email": "admin@calius.digital",
      "password": "240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9",
      "role": "admin",
      "firstName": "Admin",
      "lastName": "Calius",
      "avatar": "/assets/images/avatars/admin.jpg",
      "twoFactorEnabled": false,
      "twoFactorSecret": "",
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
  "loginHistory": [],
  "note": "Password: admin123 (SHA-256 hashed). 2FA is disabled for initial setup."
}
```

### 4. Test Website
1. Buka browser
2. Clear cache (Ctrl+Shift+Delete)
3. Akses: `https://calius.digital/`
4. Seharusnya muncul homepage

### 5. Test Admin Login
1. Akses: `https://calius.digital/admin/login.html`
2. Username: `admin`
3. Password: `admin123`
4. Authentication Code: Kosongkan atau isi `000000`
5. Klik Login
6. ✅ Seharusnya masuk ke dashboard

---

## 🐛 Troubleshooting

### Masalah: "Index of /"
**Solusi:** File ada di subfolder `cms-public/`. Pindahkan ke `public_html/`

### Masalah: 403 Forbidden
**Solusi:** 
1. Check file permissions (755 untuk folders, 644 untuk files)
2. Pastikan `.htaccess` ter-upload dengan benar

### Masalah: Login Tidak Respon
**Solusi:**
1. Pastikan file `data/users.json` ter-upload dengan password hash yang benar
2. Clear browser cache
3. Check browser console (F12) untuk error
4. Pastikan file `assets/js/auth.js` ter-upload

### Masalah: Masih Minta 2FA
**Solusi:**
1. Pastikan `data/users.json` memiliki `"twoFactorEnabled": false`
2. Clear browser cache
3. Refresh halaman login

---

## 🔐 Server-side Admin Actions

This CMS supports authenticated server-side actions (recommended):

- Login via `/admin/login.html` creates a server session.
- While logged in, the admin UI can save settings, templates, blog posts, and orders directly to the JSON files using `/admin/api/save-json.php`.
- The API enforces permissions per-file (for example, `manage_settings` is required to save `settings.json`).
Security notes:
- Passwords are stored using a modern password hash (`password_hash`) and the server will automatically migrate legacy SHA-256 passwords on first successful login. Migrated accounts will be required to change their password at next login. The login flow will redirect to `/admin/reset-password.html?token=...` where the admin can set a new password (strong-password validation is enforced).
- Sessions use secure cookie flags (`Secure`, `HttpOnly`, `SameSite=Strict`) and session IDs are regenerated on login.
- The admin API now requires a per-session CSRF token for state-changing requests (save, upload, change-password). The token is returned in the `whoami` response or on successful login and must be included in header `X-CSRF-Token` for POST requests.
- Ensure your site is served over HTTPS and consider enabling HSTS and CSP headers at the server or proxy for production.
If you'd like to script updates, use the API (authenticated session required) instead of editing files manually.

### Audit Logs

All changes made via the admin API are recorded to `data/audit.json` with timestamp, user, affected file, and a short summary of changed top-level keys. View the audit log at `/admin/audit.html` (permission: `view_analytics`).

### Running API Tests

A small test script is available at `tests/test_api.php`. To run locally:

```bash
# Ensure your local server is running and serves this project on http://localhost
php tests/test_api.php
```

The script performs basic checks: unauthorized save attempt, login, authenticated save, and 2FA setup + verification.

### Auto-upload / upload-key

Anda bisa mengaktifkan fitur auto-upload (mis. logo) yang mengirim file ke `/admin/upload-handler.php`.
Untuk keamanan, tambahkan `uploadKey` di `data/settings.json` pada bagian `security`:

```json
"security": {
  "uploadKey": "CHANGE_ME_UPLOAD_KEY"
}
```

Jika `uploadKey` di-set, unggahan harus menyertakan field `uploadKey` (form field) atau header `X-Upload-Key` dengan nilai yang cocok. Ini menambahkan lapisan proteksi di atas pemeriksaan `Referer`.

Auto-deploy to cPanel (GitHub Actions)

You can configure automatic deployment from GitHub to cPanel on every push to `main`. Steps:

1. Create an SSH key pair (on your workstation):
   - ssh-keygen -t ed25519 -f deploy_key -C "deploy@calius" -N ""
2. Add the *public* key (deploy_key.pub) to your cPanel Authorized Keys (or ~/.ssh/authorized_keys).
3. In the GitHub repo, add the following repository **Secrets**:
   - `DEPLOY_SSH_KEY` (private key contents from `deploy_key`)
   - `DEPLOY_USER` (SFTP/SSH user, e.g. `user`)
   - `DEPLOY_HOST` (hostname or IP, e.g. `example.com`)
   - `DEPLOY_PATH` (destination folder on server, e.g. `/home/user/public_html`)
4. The repository contains a workflow file `.github/workflows/deploy-to-cpanel.yml` that will run on push to `main` (or can be triggered manually). The action will rsync files (excluding `.git`, `data/`, `tests/`) into a temporary folder and then atomically swap them into place with a backup.

Notes & Safety:
- Test by pushing to a temporary branch and running the workflow manually via GitHub UI.
- The workflow will keep a timestamped backup of previous `site` folder at `DEST_PATH/backups/`.
- Do NOT commit private keys to the repository; use GitHub Secrets only.


### Template uploads and artifact storage

Uploaded template ZIPs (type `template`) are stored in a non-web directory `data/artifacts/templates/`. After upload the system computes a SHA-256 checksum and records metadata in `data/templates_artifacts.json`. Admins can download stored artifacts via the admin UI (Templates → Uploaded Template Artifacts) or using the secure endpoint `/admin/api/template-artifact-download.php?id=<artifact_id>`.

### Upload quotas & virus scanning

You can configure per-admin upload quotas and an optional virus scanner tool in `data/settings.json`:

Branding and logo:
- Upload your logo via the admin **Settings → Logo & Branding** section (or upload manually via cPanel to `/assets/images/`).
- You can run the provided PowerShell helper locally to copy your logo and optionally generate a multi-resolution `.ico` file:

```powershell
# copies logo and creates a PNG favicon fallback
.\scripts\copy-logo.ps1

# copies logo and generates favicon.ico (requires ImageMagick in PATH)
.\scripts\copy-logo.ps1 -GenerateIco
```

- After uploading or setting the `Logo URL`, use the **Extract Color from Logo** button to auto-detect a suitable brand color. This will set the site `brandColor` in `data/settings.json` and apply the color across the admin UI.


```json
"security": {
  "uploadQuotaPerDay": 100,
  "virusScannerCmd": ""  
}
```

- `uploadQuotaPerDay`: number of uploads allowed per user per day
- `virusScannerCmd`: optional command-line scanner that will be executed with the temporary uploaded file path as its argument; the upload will be rejected if the scanner exits with non-zero status (for example `clamscan --no-summary --infected`).

If you don't set `virusScannerCmd`, the upload handler will try to use `clamscan` if it's available on the server PATH. If neither are available, uploads are allowed (but it's highly recommended to install ClamAV or another scanner in production).

If you enable `virusScannerCmd`, ensure the command is available and secure. The server must have permission to execute the scanner and read the uploaded temp files.

### Running tests locally

Install dependencies (requires composer):

```bash
composer install --no-interaction --prefer-dist
./vendor/bin/phpunit --configuration phpunit.xml
```

If you don't want to install PHP/Composer locally you can run the tests inside Docker (recommended for CI or when local PHP is missing):

```bash
# Unix (build image & run tests)
./scripts/run-tests.sh

# Windows PowerShell
./scripts/run-tests.ps1
```

Both scripts build a disposable image that installs Composer dependencies and runs PHPUnit inside `/app` (the repository root).

Note: These scripts require Docker Engine to be installed and runnable on your machine. See https://docs.docker.com/get-docker/ for setup instructions.

There are also lightweight functional scripts for quick checks:

```bash
php tests/run_tests.php
php tests/test_api.php    # requires a running server at localhost
```
---

## 📝 Kredensial Default

```
URL:      https://calius.digital/admin/login.html
Username: admin
Password: admin123
2FA:      Disabled (kosongkan atau isi 000000)
```

---

## ⚡ Quick Test Commands

### Test Homepage
```bash
curl -I https://calius.digital/
# Should return: 200 OK
```

### Test Admin Login Page
```bash
curl -I https://calius.digital/admin/login.html
# Should return: 200 OK
```

### Test Data File
```bash
curl https://calius.digital/data/users.json
# Should return: 403 Forbidden (protected)
```

---

## 🎯 Next Steps After Installation

1. ✅ Login ke admin panel
2. ✅ Ganti password default
3. ✅ Update site settings di `data/settings.json`
4. ✅ Add payment gateway keys
5. ✅ Upload template files
6. ✅ Create blog posts
7. ✅ Test checkout process

---

## 📞 Need Help?

Jika masih ada masalah:
1. Check browser console (F12 → Console tab)
2. Check cPanel Error Log
3. Verify file structure
4. Verify file permissions
5. Clear all caches

---

**Good luck! 🚀**
