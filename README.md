# Calius Digital - Static CMS Website

Premium website template marketplace dengan Static CMS yang mudah dikelola melalui cPanel File Manager.

## 🚀 Fitur Utama

### Frontend (User-facing)
- ✅ **Bilingual Support** - Indonesia & English
- ✅ **High-Speed Performance** - Optimized untuk loading cepat
- ✅ **Fully Responsive** - Mobile-first design
- ✅ **5 Kategori Template** - Business, E-commerce, Portfolio, Landing Page, Restaurant
- ✅ **Blog System** - SEO-optimized blog untuk organic traffic
- ✅ **Shopping Cart** - Sistem keranjang belanja
- ✅ **Multiple Payment Gateways** - Stripe, PayPal, Midtrans
- ✅ **PWA Support** - Progressive Web App ready
- ✅ **SEO Optimized** - Meta tags, sitemap, robots.txt

### Backend (Admin Panel)
- ✅ **Secure Login** - Password + Google Authenticator 2FA
- ✅ **Dashboard** - Statistik penjualan dan overview
- ✅ **Template Manager** - Kelola template (add/edit/delete)
- ✅ **Blog Manager** - Kelola artikel blog
- ✅ **Order Manager** - Kelola pesanan
- ✅ **Settings Manager** - Konfigurasi website
- ✅ **File-based Database** - JSON files (mudah backup)

## 📁 Struktur Folder

```
public/
├── index.html                      # Homepage
├── templates.html                  # Template showcase
├── blog.html                       # Blog listing
├── contact.html                    # Contact page
├── about.html                      # About page
├── checkout.html                   # Checkout page
│
├── admin/                          # Admin Panel (Protected)
│   ├── login.html                  # Login dengan 2FA
│   ├── index.html                  # Dashboard
│   ├── templates-manager.html      # Kelola templates
│   ├── blog-manager.html           # Kelola blog
│   ├── orders-manager.html         # Kelola orders
│   └── settings.html               # Settings
│
├── assets/
│   ├── css/
│   │   ├── main.css                # Main stylesheet
│   │   ├── admin.css               # Admin panel styles
│   │   └── responsive.css          # Responsive styles
│   │
│   ├── js/
│   │   ├── main.js                 # Main JavaScript
│   │   ├── cms.js                  # CMS functionality
│   │   ├── auth.js                 # Authentication + 2FA
│   │   ├── language.js             # Language switcher
│   │   ├── payment.js              # Payment integration
│   │   └── lazy-load.js            # Lazy loading
│   │
│   └── images/                     # Images & assets
│
├── data/                           # Database (JSON files)
│   ├── templates.json              # Template database
│   ├── blog.json                   # Blog posts
│   ├── orders.json                 # Orders
│   ├── settings.json               # Site settings
│   └── users.json                  # Admin users
│
├── .htaccess                       # Security & optimization
├── robots.txt                      # SEO
├── sitemap.xml                     # SEO
└── manifest.json                   # PWA
```

## 🔧 Instalasi via cPanel

### 1. Upload Files
1. Login ke cPanel
2. Buka **File Manager**
3. Navigate ke folder `public_html` (ini adalah root directory website Anda)
4. **PENTING:** Upload semua file LANGSUNG ke `public_html/`, BUKAN ke subfolder
   - Struktur yang benar:
     ```
     public_html/
     ├── index.html          ← Harus di sini
     ├── .htaccess           ← Harus di sini
     ├── templates.html
     ├── blog.html
     ├── admin/
     ├── assets/
     ├── data/
     └── ...
     ```
   - Struktur yang SALAH:
     ```
     public_html/
     └── public/             ← JANGAN seperti ini
         ├── index.html
         ├── .htaccess
         └── ...
     ```
5. Jika file ada di dalam folder `public/`, pindahkan semua isinya ke `public_html/`

### 2. Set Permissions
```
Folders: 755
Files: 644
data/ folder: 755 (untuk write access)
```

### 3. Verifikasi Struktur
Pastikan ketika Anda buka File Manager dan masuk ke `public_html/`, Anda langsung melihat:
- index.html
- .htaccess
- templates.html
- folder admin/
- folder assets/
- folder data/

### 3. Konfigurasi Payment Gateway

Edit file `/data/settings.json`:

**Stripe:**
```json
"stripe": {
  "enabled": true,
  "publicKey": "pk_live_YOUR_STRIPE_PUBLIC_KEY",
  "currency": "USD"
}
```

**PayPal:**
```json
"paypal": {
  "enabled": true,
  "clientId": "YOUR_PAYPAL_CLIENT_ID",
  "currency": "USD",
  "environment": "production"
}
```

**Midtrans:**
```json
"midtrans": {
  "enabled": true,
  "clientKey": "YOUR_MIDTRANS_CLIENT_KEY",
  "serverKey": "YOUR_MIDTRANS_SERVER_KEY",
  "environment": "production",
  "currency": "IDR"
}
```

### 4. Setup SSL (Recommended)
1. Di cPanel, buka **SSL/TLS Status**
2. Install SSL certificate (Let's Encrypt gratis)
3. Uncomment baris HTTPS redirect di `.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## 🔐 Login Admin

### Default Credentials
- **URL:** `https://calius.digital/admin/login.html`
- **Username:** `admin`
- **Password:** `admin123`
- **2FA:** Disabled (untuk initial setup)

⚠️ **PENTING:** Ganti password setelah login pertama!

### Cara Login (2FA Disabled)
1. Buka `https://calius.digital/admin/login.html`
2. Masukkan username: `admin`
3. Masukkan password: `admin123`
4. **Kosongkan field "Authentication Code"** atau isi dengan angka apa saja (000000)
5. Klik Login
6. Anda akan masuk ke dashboard

### Setup Google Authenticator (2FA) - Optional
**Catatan:** 2FA sudah dinonaktifkan untuk memudahkan initial setup. Anda bisa mengaktifkannya nanti untuk keamanan lebih baik.

1. Login ke admin panel
2. Buka Settings → Security
3. Enable Two-Factor Authentication
4. Scan QR code dengan Google Authenticator app
5. Simpan backup codes di tempat aman
6. Update `data/users.json` → set `"twoFactorEnabled": true`

## 📝 Cara Menggunakan CMS

### Menambah Template Baru

1. Login ke admin panel
2. Klik **Templates** di sidebar
3. Klik **Add New Template**
4. Isi form:
   - Name (EN & ID)
   - Category
   - Price
   - Description
   - Features
   - Upload preview images
5. Klik **Save**
6. **PENTING:** Copy JSON yang muncul
7. Buka cPanel File Manager
8. Edit `/data/templates.json`
9. Paste JSON yang di-copy
10. Save file

### Menambah Blog Post

1. Login ke admin panel
2. Klik **Blog Posts** di sidebar
3. Klik **Create New Post**
4. Isi form:
   - Title (EN & ID)
   - Content (EN & ID)
   - Category
   - Featured image
   - SEO settings
5. Klik **Save Draft** atau **Publish**
6. Copy JSON dan update `/data/blog.json` via cPanel

### Mengelola Orders

1. Login ke admin panel
2. Klik **Orders** di sidebar
3. View order details
4. Update order status
5. Send download links to customers

## 💳 Payment Gateway Setup

### Stripe
1. Daftar di [stripe.com](https://stripe.com)
2. Get API keys dari Dashboard
3. Update `settings.json` dengan keys
4. Test dengan test cards

### PayPal
1. Daftar di [paypal.com/developer](https://developer.paypal.com)
2. Create app untuk get Client ID
3. Update `settings.json`
4. Switch ke production mode

### Midtrans
1. Daftar di [midtrans.com](https://midtrans.com)
2. Get Client Key & Server Key
3. Update `settings.json`
4. Test dengan sandbox mode

## 🎨 Customization

### Mengubah Warna
Edit `/assets/css/main.css`:
```css
:root {
  --primary-color: #2563eb;    /* Warna utama */
  --secondary-color: #10b981;  /* Warna sekunder */
  --accent-color: #f59e0b;     /* Warna aksen */
}
```

### Mengubah Logo
1. Upload logo baru ke `/assets/images/`
2. Update di `index.html`:
```html
<a href="/" class="logo">
    <img src="/assets/images/your-logo.svg" alt="Logo">
</a>
```

### Menambah Bahasa Baru
Edit `/assets/js/language.js` dan tambahkan translations.

## 🔒 Security Best Practices

1. **Ganti Default Password** segera setelah instalasi
2. **Enable 2FA** untuk semua admin users
3. **Backup data** secara regular (download folder `/data`)
4. **Update** file secara berkala
5. **Monitor** login attempts di admin panel
6. **Use SSL** untuk semua halaman
7. **Restrict** admin folder dengan IP whitelist (optional)

### Emergency Password Reset

Jika Anda terkunci dari akun (mis. 2FA tidak tersedia), Anda bisa mengaktifkan mekanisme emergency reset:

- Tambahkan pada `/data/settings.json`:

```json
"security": {
  "emergencyResetKey": "your-strong-key-here",
  "emergencyResetLimitPerHour": 5
}
```
- Panggil endpoint `POST /admin/api/request-password-reset.php` dengan body JSON `{ "username": "admin", "emergencyKey": "your-strong-key-here" }`.
- Endpoint ini rate-limited dan hanya mengeluarkan token jika `emergencyResetKey` cocok.

### IP Whitelist (Optional)
Tambahkan di `.htaccess`:
```apache
<Directory "/admin">
    Order Deny,Allow
    Deny from all
    Allow from YOUR_IP_ADDRESS
</Directory>
```

## 📊 SEO Optimization

### Sitemap
- Auto-generated di `/sitemap.xml`
- Submit ke Google Search Console
- Update setiap ada template/blog baru

### Meta Tags
- Sudah ter-optimasi di setiap halaman
- Edit di masing-masing HTML file
- Gunakan bilingual meta tags

### Performance
- Lazy loading images ✅
- Minified CSS/JS ✅
- Browser caching ✅
- Gzip compression ✅
- CDN ready ✅

## 🐛 Troubleshooting

### Admin Panel tidak bisa diakses
1. Check file permissions (755 untuk folders)
2. Check `.htaccess` configuration
3. Clear browser cache
4. Check browser console untuk errors

### Payment tidak bekerja
1. Verify API keys di `settings.json`
2. Check payment gateway status
3. Test dengan sandbox/test mode
4. Check browser console untuk errors

### Images tidak muncul
1. Check file paths
2. Verify image files uploaded
3. Check permissions (644 untuk files)
4. Clear browser cache

### JSON file tidak ter-update
1. Check file permissions (755 untuk `/data` folder)
2. Manual update via cPanel File Manager
3. Validate JSON syntax di [jsonlint.com](https://jsonlint.com)

## 📞 Support

Untuk bantuan lebih lanjut:
- Email: support@calius.digital
- Documentation: [docs.calius.digital](https://docs.calius.digital)

## 📄 License

© 2024 Calius Digital. All rights reserved.

## 🔄 Updates

### Version 1.0.0 (Current)
- Initial release
- Static CMS functionality
- Bilingual support
- Multiple payment gateways
- Admin panel dengan 2FA
- Blog system
- SEO optimization

---

**Dibuat dengan ❤️ untuk kemudahan pengelolaan website template marketplace**
