/* ============================================
   CALIUS DIGITAL - Language Management
   Bilingual Support (English & Indonesian)
   ============================================ */

(function() {
  'use strict';

  // ============================================
  // Language Translations
  // ============================================
  
  const translations = {
    // Navigation
    'nav.home': {
      en: 'Home',
      id: 'Beranda'
    },
    'nav.templates': {
      en: 'Templates',
      id: 'Template'
    },
    'nav.blog': {
      en: 'Blog',
      id: 'Blog'
    },
    'nav.about': {
      en: 'About',
      id: 'Tentang'
    },
    'nav.contact': {
      en: 'Contact',
      id: 'Kontak'
    },

    // Hero Section
    'hero.title': {
      en: 'Premium High-Speed Website Templates',
      id: 'Template Website Premium Berkecepatan Tinggi'
    },
    'hero.subtitle': {
      en: 'Build stunning websites with our lightning-fast templates. Perfect for businesses, e-commerce, portfolios, and more.',
      id: 'Bangun website menakjubkan dengan template super cepat kami. Sempurna untuk bisnis, e-commerce, portfolio, dan lainnya.'
    },
    'hero.cta.browse': {
      en: 'Browse Templates',
      id: 'Lihat Template'
    },
    'hero.cta.learn': {
      en: 'Learn More',
      id: 'Pelajari Lebih Lanjut'
    },

    // Categories
    'category.business': {
      en: 'Business',
      id: 'Bisnis'
    },
    'category.ecommerce': {
      en: 'E-commerce',
      id: 'E-commerce'
    },
    'category.portfolio': {
      en: 'Portfolio',
      id: 'Portfolio'
    },
    'category.landing-page': {
      en: 'Landing Page',
      id: 'Landing Page'
    },
    'category.restaurant': {
      en: 'Restaurant',
      id: 'Restoran'
    },

    // Template Page
    'templates.title': {
      en: 'Premium Website Templates',
      id: 'Template Website Premium'
    },
    'templates.subtitle': {
      en: 'Choose from our collection of high-performance templates',
      id: 'Pilih dari koleksi template performa tinggi kami'
    },
    'templates.filter.all': {
      en: 'All Templates',
      id: 'Semua Template'
    },
    'templates.filter.featured': {
      en: 'Featured',
      id: 'Unggulan'
    },
    'templates.filter.bestseller': {
      en: 'Best Sellers',
      id: 'Terlaris'
    },
    'templates.filter.new': {
      en: 'New',
      id: 'Baru'
    },
    'templates.sort.popular': {
      en: 'Most Popular',
      id: 'Terpopuler'
    },
    'templates.sort.price-low': {
      en: 'Price: Low to High',
      id: 'Harga: Rendah ke Tinggi'
    },
    'templates.sort.price-high': {
      en: 'Price: High to Low',
      id: 'Harga: Tinggi ke Rendah'
    },
    'templates.sort.newest': {
      en: 'Newest First',
      id: 'Terbaru'
    },

    // Template Card
    'template.view-demo': {
      en: 'View Demo',
      id: 'Lihat Demo'
    },
    'template.add-to-cart': {
      en: 'Add to Cart',
      id: 'Tambah ke Keranjang'
    },
    'template.buy-now': {
      en: 'Buy Now',
      id: 'Beli Sekarang'
    },
    'template.features': {
      en: 'Features',
      id: 'Fitur'
    },
    'template.pages': {
      en: 'Pages',
      id: 'Halaman'
    },
    'template.downloads': {
      en: 'Downloads',
      id: 'Unduhan'
    },
    'template.rating': {
      en: 'Rating',
      id: 'Rating'
    },

    // Blog
    'blog.title': {
      en: 'Blog & Resources',
      id: 'Blog & Sumber Daya'
    },
    'blog.subtitle': {
      en: 'Tips, guides, and insights for web development',
      id: 'Tips, panduan, dan wawasan untuk pengembangan web'
    },
    'blog.read-more': {
      en: 'Read More',
      id: 'Baca Selengkapnya'
    },
    'blog.read-time': {
      en: 'min read',
      id: 'menit baca'
    },
    'blog.published': {
      en: 'Published',
      id: 'Dipublikasikan'
    },
    'blog.category': {
      en: 'Category',
      id: 'Kategori'
    },
    'blog.tags': {
      en: 'Tags',
      id: 'Tag'
    },
    'blog.share': {
      en: 'Share',
      id: 'Bagikan'
    },

    // Cart
    'cart.title': {
      en: 'Shopping Cart',
      id: 'Keranjang Belanja'
    },
    'cart.empty': {
      en: 'Your cart is empty',
      id: 'Keranjang Anda kosong'
    },
    'cart.item': {
      en: 'Item',
      id: 'Item'
    },
    'cart.price': {
      en: 'Price',
      id: 'Harga'
    },
    'cart.quantity': {
      en: 'Quantity',
      id: 'Jumlah'
    },
    'cart.total': {
      en: 'Total',
      id: 'Total'
    },
    'cart.subtotal': {
      en: 'Subtotal',
      id: 'Subtotal'
    },
    'cart.tax': {
      en: 'Tax',
      id: 'Pajak'
    },
    'cart.checkout': {
      en: 'Proceed to Checkout',
      id: 'Lanjut ke Pembayaran'
    },
    'cart.continue-shopping': {
      en: 'Continue Shopping',
      id: 'Lanjut Belanja'
    },
    'cart.remove': {
      en: 'Remove',
      id: 'Hapus'
    },

    // Checkout
    'checkout.title': {
      en: 'Checkout',
      id: 'Pembayaran'
    },
    'checkout.billing-info': {
      en: 'Billing Information',
      id: 'Informasi Pembayaran'
    },
    'checkout.payment-method': {
      en: 'Payment Method',
      id: 'Metode Pembayaran'
    },
    'checkout.order-summary': {
      en: 'Order Summary',
      id: 'Ringkasan Pesanan'
    },
    'checkout.place-order': {
      en: 'Place Order',
      id: 'Buat Pesanan'
    },

    // Forms
    'form.first-name': {
      en: 'First Name',
      id: 'Nama Depan'
    },
    'form.last-name': {
      en: 'Last Name',
      id: 'Nama Belakang'
    },
    'form.email': {
      en: 'Email Address',
      id: 'Alamat Email'
    },
    'form.phone': {
      en: 'Phone Number',
      id: 'Nomor Telepon'
    },
    'form.country': {
      en: 'Country',
      id: 'Negara'
    },
    'form.message': {
      en: 'Message',
      id: 'Pesan'
    },
    'form.submit': {
      en: 'Submit',
      id: 'Kirim'
    },
    'form.required': {
      en: 'Required field',
      id: 'Wajib diisi'
    },

    // Contact
    'contact.title': {
      en: 'Contact Us',
      id: 'Hubungi Kami'
    },
    'contact.subtitle': {
      en: 'Get in touch with our team',
      id: 'Hubungi tim kami'
    },
    'contact.send-message': {
      en: 'Send Message',
      id: 'Kirim Pesan'
    },

    // Footer
    'footer.about': {
      en: 'About Calius Digital',
      id: 'Tentang Calius Digital'
    },
    'footer.about-text': {
      en: 'Premium high-speed website templates for global market. Build stunning websites with guaranteed performance.',
      id: 'Template website premium berkecepatan tinggi untuk pasar global. Bangun website menakjubkan dengan performa terjamin.'
    },
    'footer.quick-links': {
      en: 'Quick Links',
      id: 'Tautan Cepat'
    },
    'footer.categories': {
      en: 'Categories',
      id: 'Kategori'
    },
    'footer.support': {
      en: 'Support',
      id: 'Dukungan'
    },
    'footer.faq': {
      en: 'FAQ',
      id: 'FAQ'
    },
    'footer.documentation': {
      en: 'Documentation',
      id: 'Dokumentasi'
    },
    'footer.terms': {
      en: 'Terms of Service',
      id: 'Syarat Layanan'
    },
    'footer.privacy': {
      en: 'Privacy Policy',
      id: 'Kebijakan Privasi'
    },
    'footer.newsletter': {
      en: 'Newsletter',
      id: 'Newsletter'
    },
    'footer.newsletter-text': {
      en: 'Subscribe to get updates on new templates and offers',
      id: 'Berlangganan untuk mendapat update template baru dan penawaran'
    },
    'footer.subscribe': {
      en: 'Subscribe',
      id: 'Berlangganan'
    },
    'footer.copyright': {
      en: '© 2024 Calius Digital. All rights reserved.',
      id: '© 2024 Calius Digital. Hak cipta dilindungi.'
    },

    // Notifications
    'notification.success': {
      en: 'Success!',
      id: 'Berhasil!'
    },
    'notification.error': {
      en: 'Error!',
      id: 'Error!'
    },
    'notification.warning': {
      en: 'Warning!',
      id: 'Peringatan!'
    },
    'notification.info': {
      en: 'Info',
      id: 'Info'
    },
    'notification.added-to-cart': {
      en: 'Added to cart successfully',
      id: 'Berhasil ditambahkan ke keranjang'
    },
    'notification.removed-from-cart': {
      en: 'Removed from cart',
      id: 'Dihapus dari keranjang'
    },
    'notification.order-placed': {
      en: 'Order placed successfully',
      id: 'Pesanan berhasil dibuat'
    },

    // Search
    'search.placeholder': {
      en: 'Search templates, blog posts...',
      id: 'Cari template, artikel blog...'
    },
    'search.no-results': {
      en: 'No results found',
      id: 'Tidak ada hasil'
    },
    'search.results': {
      en: 'Search Results',
      id: 'Hasil Pencarian'
    },

    // Buttons
    'button.view-all': {
      en: 'View All',
      id: 'Lihat Semua'
    },
    'button.load-more': {
      en: 'Load More',
      id: 'Muat Lebih Banyak'
    },
    'button.back': {
      en: 'Back',
      id: 'Kembali'
    },
    'button.next': {
      en: 'Next',
      id: 'Selanjutnya'
    },
    'button.previous': {
      en: 'Previous',
      id: 'Sebelumnya'
    },
    'button.close': {
      en: 'Close',
      id: 'Tutup'
    },
    'button.cancel': {
      en: 'Cancel',
      id: 'Batal'
    },
    'button.save': {
      en: 'Save',
      id: 'Simpan'
    },
    'button.delete': {
      en: 'Delete',
      id: 'Hapus'
    },
    'button.edit': {
      en: 'Edit',
      id: 'Edit'
    }
  };

  // ============================================
  // Language Manager
  // ============================================
  
  class LanguageManager {
    constructor() {
      this.currentLanguage = localStorage.getItem('calius_language') || 'en';
      this.translations = translations;
    }

    setLanguage(lang) {
      if (!['en', 'id'].includes(lang)) {
        console.error('Unsupported language:', lang);
        return;
      }

      this.currentLanguage = lang;
      localStorage.setItem('calius_language', lang);
      document.documentElement.lang = lang;
      
      this.updatePageContent();
      this.updateActiveButton();
      
      // Dispatch event for other components
      window.dispatchEvent(new CustomEvent('languageChanged', { detail: { language: lang } }));
    }

    getLanguage() {
      return this.currentLanguage;
    }

    translate(key) {
      const translation = this.translations[key];
      if (!translation) {
        console.warn('Translation not found for key:', key);
        return key;
      }
      return translation[this.currentLanguage] || translation.en || key;
    }

    updatePageContent() {
      // Update all elements with data-i18n attribute
      document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        const translation = this.translate(key);
        
        if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
          element.placeholder = translation;
        } else {
          element.textContent = translation;
        }
      });

      // Update all elements with data-i18n-html attribute (for HTML content)
      document.querySelectorAll('[data-i18n-html]').forEach(element => {
        const key = element.getAttribute('data-i18n-html');
        element.innerHTML = this.translate(key);
      });

      // Update all elements with data-i18n-title attribute (for tooltips)
      document.querySelectorAll('[data-i18n-title]').forEach(element => {
        const key = element.getAttribute('data-i18n-title');
        element.title = this.translate(key);
      });
    }

    updateActiveButton() {
      document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.lang === this.currentLanguage);
      });
    }

    formatPrice(amount, currency = 'USD') {
      const locale = this.currentLanguage === 'id' ? 'id-ID' : 'en-US';
      return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency
      }).format(amount);
    }

    formatDate(date, options = {}) {
      const locale = this.currentLanguage === 'id' ? 'id-ID' : 'en-US';
      const defaultOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      };
      return new Intl.DateTimeFormat(locale, { ...defaultOptions, ...options }).format(new Date(date));
    }

    formatNumber(number) {
      const locale = this.currentLanguage === 'id' ? 'id-ID' : 'en-US';
      return new Intl.NumberFormat(locale).format(number);
    }
  }

  // ============================================
  // Initialize Language Manager
  // ============================================
  
  const languageManager = new LanguageManager();

  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
    // Apply current language
    languageManager.updatePageContent();
    languageManager.updateActiveButton();

    // Setup language switcher buttons
    document.querySelectorAll('.lang-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const lang = this.dataset.lang;
        languageManager.setLanguage(lang);
      });
    });
  });

  // ============================================
  // Export Language Manager
  // ============================================
  
  window.LanguageManager = languageManager;
  
  // Shorthand for translation
  window.t = (key) => languageManager.translate(key);

  console.log('Language Manager initialized');

})();
