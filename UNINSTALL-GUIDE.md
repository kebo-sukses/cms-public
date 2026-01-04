# 🗑️ Panduan Uninstall - Calius Digital CMS

Panduan lengkap untuk menghapus/uninstall CMS Calius Digital dari server Anda.

## ⚠️ PERINGATAN PENTING

**BACKUP DATA TERLEBIH DAHULU!**

Sebelum melakukan uninstall, pastikan Anda sudah:
- ✅ Backup semua file di `data/` (settings, templates, blog, orders, users)
- ✅ Backup database (jika ada)
- ✅ Download semua file yang mungkin diperlukan di masa depan
- ✅ Ekspor data penting ke format lain (CSV, JSON, dll)

**Proses uninstall bersifat PERMANEN dan TIDAK DAPAT DIBATALKAN!**

---

## 📋 Metode Uninstall

### Metode 1: Via cPanel File Manager (Recommended)

Ini adalah cara termudah dan tercepat untuk menghapus CMS.

#### Langkah-langkah:

1. **Login ke cPanel**
   - Akses cPanel hosting Anda
   - Login dengan kredensial cPanel

2. **Buka File Manager**
   - Cari dan klik icon "File Manager"
   - Navigate ke `public_html/`

3. **Backup Data (WAJIB)**
   - Select folder `data/`
   - Klik kanan → Compress → Create Archive
   - Download file archive yang sudah dibuat
   - Simpan backup di komputer lokal Anda

4. **Hapus Semua File CMS**
   
   **Opsi A: Hapus Semua (Full Clean)**
   ```
   Pilih semua file dan folder di public_html/:
   - Select All (Ctrl+A)
   - Klik Delete
   - Confirm deletion
   ```
   
   **Opsi B: Hapus Selektif (Keep Other Files)**
   ```
   Hanya pilih file/folder CMS Calius Digital:
   - index.html
   - about.html
   - blog.html
   - checkout.html
   - contact.html
   - templates.html
   - 403.html, 404.html, 500.html
   - .htaccess (jika khusus untuk CMS ini)
   - admin/ (folder)
   - assets/ (folder)
   - data/ (folder)
   - scripts/ (folder)
   - tests/ (folder)
   - manifest.json
   - robots.txt (jika khusus untuk CMS ini)
   - sitemap.xml (jika khusus untuk CMS ini)
   - composer.json
   - phpunit.xml
   - Dockerfile
   - .dockerignore
   - README.md (jika khusus untuk CMS ini)
   - TODO.md
   - INSTALL-GUIDE.md
   - UNINSTALL-GUIDE.md
   
   Klik kanan → Delete
   Confirm deletion
   ```

5. **Verifikasi Penghapusan**
   - Refresh File Manager
   - Pastikan semua file CMS sudah terhapus
   - Akses website Anda di browser
   - Seharusnya muncul blank page atau default hosting page

---

### Metode 2: Via FTP Client

Jika Anda prefer menggunakan FTP client seperti FileZilla.

#### Langkah-langkah:

1. **Connect ke Server**
   - Buka FTP client (FileZilla, WinSCP, dll)
   - Connect menggunakan kredensial FTP
   - Navigate ke folder `public_html/`

2. **Backup Data**
   - Download folder `data/` ke komputer lokal
   - Pastikan semua file ter-download dengan lengkap

3. **Delete Files**
   - Select semua file/folder CMS (lihat daftar di Metode 1)
   - Right-click → Delete
   - Confirm deletion

4. **Verify**
   - Refresh FTP client
   - Check browser untuk memastikan site sudah tidak ada

---

### Metode 3: Via SSH/Terminal (Advanced)

Untuk pengguna advanced yang memiliki SSH access.

#### Langkah-langkah:

1. **Login via SSH**
   ```bash
   ssh username@your-domain.com
   ```

2. **Navigate ke Directory**
   ```bash
   cd ~/public_html
   ```

3. **Backup Data (WAJIB)**
   ```bash
   # Create backup directory
   mkdir -p ~/backups/cms-calius-$(date +%Y%m%d)
   
   # Backup data folder
   cp -r data/ ~/backups/cms-calius-$(date +%Y%m%d)/
   
   # Create tar archive
   tar -czf ~/backups/cms-calius-$(date +%Y%m%d).tar.gz data/
   
   # Verify backup
   ls -lh ~/backups/
   ```

4. **Delete CMS Files**
   
   **Opsi A: Hapus Semua (Full Clean)**
   ```bash
   # PERINGATAN: Ini akan menghapus SEMUA file di public_html!
   cd ~/public_html
   rm -rf *
   rm -rf .[^.]*  # Remove hidden files
   ```
   
   **Opsi B: Hapus Selektif (Recommended)**
   ```bash
   cd ~/public_html
   
   # Remove HTML files
   rm -f index.html about.html blog.html checkout.html contact.html templates.html
   rm -f 403.html 404.html 500.html
   
   # Remove folders
   rm -rf admin/
   rm -rf assets/
   rm -rf data/
   rm -rf scripts/
   rm -rf tests/
   
   # Remove config files
   rm -f .htaccess
   rm -f manifest.json
   rm -f robots.txt
   rm -f sitemap.xml
   rm -f composer.json
   rm -f phpunit.xml
   rm -f Dockerfile
   rm -f .dockerignore
   
   # Remove documentation
   rm -f README.md
   rm -f TODO.md
   rm -f INSTALL-GUIDE.md
   rm -f UNINSTALL-GUIDE.md
   ```

5. **Verify Deletion**
   ```bash
   # List remaining files
   ls -la
   
   # Check disk usage
   du -sh
   ```

---

## 🔄 Post-Uninstall Cleanup

### 1. Database Cleanup (Jika Ada)

Jika CMS menggunakan database (untuk versi future):

```sql
-- Login ke MySQL
mysql -u username -p

-- Show databases
SHOW DATABASES;

-- Drop database
DROP DATABASE calius_cms;

-- Exit
EXIT;
```

Via cPanel phpMyAdmin:
1. Login ke cPanel
2. Buka phpMyAdmin
3. Select database CMS
4. Click "Drop" → Confirm

### 2. Clear DNS/Domain Settings

Jika domain dedicated untuk CMS ini:

1. **Via cPanel:**
   - Domains → Remove domain
   - Atau redirect ke domain lain

2. **Via DNS Provider:**
   - Update A record
   - Atau delete DNS entries

### 3. SSL Certificate Cleanup

Jika menggunakan SSL khusus untuk CMS:

1. Via cPanel SSL/TLS:
   - Manage SSL sites
   - Uninstall certificate

2. Via Let's Encrypt:
   ```bash
   certbot delete --cert-name your-domain.com
   ```

### 4. Clear CDN Cache (Jika Digunakan)

Jika menggunakan Cloudflare atau CDN lain:

1. Login ke Cloudflare Dashboard
2. Caching → Purge Everything
3. Atau delete zone jika tidak digunakan lagi

---

## 📊 Checklist Uninstall

Gunakan checklist ini untuk memastikan uninstall lengkap:

### Pre-Uninstall
- [ ] Backup folder `data/` (settings, templates, blog, orders, users)
- [ ] Backup template files (jika ada)
- [ ] Ekspor data penting ke format lain
- [ ] Screenshot admin panel (untuk referensi)
- [ ] Save payment gateway settings
- [ ] Save API keys dan credentials
- [ ] Notify users (jika applicable)

### Uninstall Process
- [ ] Login ke cPanel/FTP/SSH
- [ ] Navigate ke directory yang benar
- [ ] Delete semua file CMS (sesuai daftar)
- [ ] Delete semua folder CMS (sesuai daftar)
- [ ] Verify deletion dengan refresh/ls

### Post-Uninstall
- [ ] Clear browser cache
- [ ] Check website di browser (pastikan blank/default)
- [ ] Clear CDN cache (jika ada)
- [ ] Remove database (jika ada)
- [ ] Remove SSL certificate (jika khusus untuk CMS)
- [ ] Update DNS settings (jika perlu)
- [ ] Remove cron jobs (jika ada)
- [ ] Clean up email accounts (jika ada)

### Final Verification
- [ ] Website tidak dapat diakses atau menampilkan default page
- [ ] Admin panel tidak dapat diakses
- [ ] API endpoints tidak respond
- [ ] Backup tersimpan dengan aman
- [ ] Domain/subdomain sudah di-redirect atau dihapus

---

## 🆘 Troubleshooting

### Masalah: File Tidak Bisa Dihapus

**Penyebab:** Permission issue atau file sedang digunakan

**Solusi:**
```bash
# Via SSH, coba dengan sudo (jika tersedia)
sudo rm -rf public_html/*

# Atau ubah ownership terlebih dahulu
sudo chown -R $USER:$USER public_html/

# Kemudian hapus
rm -rf public_html/*

# Jika masih gagal, ubah permission minimal yang diperlukan
chmod -R u+w public_html/  # Add write permission untuk user
rm -rf public_html/*
```

Via cPanel:
1. Select file yang tidak bisa dihapus
2. Change Permissions → Set to 755 (folders) atau 644 (files)
3. Jika masih gagal, hubungi hosting support untuk bantuan
4. **HINDARI menggunakan 777** karena membuka celah keamanan

### Masalah: .htaccess Menyebabkan Error

**Penyebab:** .htaccess masih aktif setelah partial delete

**Solusi:**
```bash
# Hapus atau rename .htaccess
mv .htaccess .htaccess.old

# Atau delete langsung
rm -f .htaccess
```

### Masalah: Website Masih Menampilkan CMS

**Penyebab:** Browser cache atau CDN cache

**Solusi:**
1. Clear browser cache (Ctrl+Shift+Delete)
2. Clear CDN cache (jika menggunakan Cloudflare/CDN)
3. Try incognito/private mode
4. Try dari device/network lain

### Masalah: Data Loss

**Penyebab:** Tidak backup sebelum uninstall

**Solusi:**
- Jika baru saja delete, hubungi hosting support
- Beberapa hosting provider memiliki backup otomatis
- Check cPanel Backup → Download Backup
- Request restore dari hosting provider (mungkin ada biaya)

---

## 🔐 Security Notes

Setelah uninstall, pastikan:

1. **Remove Sensitive Data:**
   - Delete users.json (contains password hashes)
   - Delete orders.json (contains customer data)
   - Delete payment gateway credentials
   - Delete API keys

2. **Clean Server Logs:**
   ```bash
   # Clear access logs
   > ~/logs/access_log
   
   # Clear error logs
   > ~/logs/error_log
   ```

3. **Revoke API Access:**
   - Deactivate Stripe API keys
   - Deactivate PayPal API credentials
   - Deactivate Midtrans API keys
   - Remove webhook endpoints

---

## 💾 Data Migration (Before Uninstall)

Jika Anda ingin migrasi ke platform lain sebelum uninstall:

### Export Templates
```bash
# Backup templates.json
cp data/templates.json ~/backup/templates-export.json

# Convert to CSV (manual or script)
# Open templates.json
# Extract data ke spreadsheet
```

### Export Blog Posts
```bash
# Backup blog.json
cp data/blog.json ~/backup/blog-export.json

# Import ke WordPress/Ghost/Medium
# Sesuaikan format dengan platform tujuan
```

### Export Orders
```bash
# Backup orders.json
cp data/orders.json ~/backup/orders-export.json

# Import ke accounting software
# Convert to CSV for spreadsheet
```

### Export Users
```bash
# Backup users.json
cp data/users.json ~/backup/users-export.json

# NOTE: Password sudah di-hash, tidak bisa di-export plain text
# Users perlu reset password di platform baru
```

---

## 📞 Need Help?

Jika mengalami kesulitan saat uninstall:

1. **Check Hosting Documentation:**
   - Baca panduan dari hosting provider Anda
   - Cari tutorial "how to delete website files"

2. **Contact Hosting Support:**
   - Buka ticket support
   - Minta bantuan untuk menghapus files
   - Beberapa provider bisa remote bantuan

3. **Community Support:**
   - Check forum hosting Anda
   - Search di Google dengan error message
   - Ask di StackOverflow atau Reddit

---

## 🚀 Alternative: Fresh Install

Jika Anda ingin uninstall untuk fresh install:

1. **Backup data/**
2. **Delete semua file KECUALI data/**
3. **Upload fresh files dari repository**
4. **Restore data/ dari backup**
5. **Test website**

Ini lebih aman daripada full uninstall + reinstall.

---

## ✅ Verification Commands

Untuk memastikan uninstall berhasil:

```bash
# Check if files exist
ls -la ~/public_html/

# Check disk usage
du -sh ~/public_html/

# Check website response
curl -I https://your-domain.com/

# Check admin panel
curl -I https://your-domain.com/admin/

# Check data folder
ls -la ~/public_html/data/
```

Expected results setelah uninstall:
- `ls -la` → Empty atau hanya file non-CMS
- `curl -I` → 404 Not Found atau default page
- `data/` → Not found

---

## 📝 Notes

- Proses uninstall biasanya memakan waktu 5-10 menit
- Pastikan tidak ada transaksi aktif sebelum uninstall
- Inform users jika website public
- Keep backup minimal 30 hari setelah uninstall
- Document alasan uninstall untuk future reference

---

**Last Updated:** 2026-01-04  
**Status:** Complete Uninstall Guide  
**Language:** Bahasa Indonesia (Indonesian)

---

Untuk pertanyaan lebih lanjut tentang uninstall, silakan buka issue di GitHub repository.
