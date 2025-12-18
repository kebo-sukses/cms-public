/* ============================================
   CALIUS DIGITAL - Lazy Loading
   High-Performance Image & Content Loading
   ============================================ */

(function() {
  'use strict';

  // ============================================
  // Lazy Load Configuration
  // ============================================
  
  const config = {
    rootMargin: '50px 0px',
    threshold: 0.01,
    loadingClass: 'lazy-loading',
    loadedClass: 'lazy-loaded',
    errorClass: 'lazy-error',
    placeholderColor: '#f3f4f6'
  };

  // ============================================
  // Image Lazy Loader
  // ============================================
  
  class ImageLazyLoader {
    constructor() {
      this.images = [];
      this.observer = null;
      this.initialize();
    }

    initialize() {
      // Check for native lazy loading support
      if ('loading' in HTMLImageElement.prototype) {
        this.useNativeLazyLoading();
      } else {
        this.useIntersectionObserver();
      }
    }

    useNativeLazyLoading() {
      const images = document.querySelectorAll('img[data-src]');
      images.forEach(img => {
        img.loading = 'lazy';
        img.src = img.dataset.src;
        if (img.dataset.srcset) {
          img.srcset = img.dataset.srcset;
        }
        img.classList.add(config.loadedClass);
      });
    }

    useIntersectionObserver() {
      this.images = Array.from(document.querySelectorAll('img[data-src]'));
      
      if (this.images.length === 0) return;

      this.observer = new IntersectionObserver(
        this.onIntersection.bind(this),
        {
          rootMargin: config.rootMargin,
          threshold: config.threshold
        }
      );

      this.images.forEach(img => {
        this.observer.observe(img);
        this.addPlaceholder(img);
      });
    }

    onIntersection(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          this.loadImage(entry.target);
          this.observer.unobserve(entry.target);
        }
      });
    }

    loadImage(img) {
      img.classList.add(config.loadingClass);

      const src = img.dataset.src;
      const srcset = img.dataset.srcset;

      // Create a new image to preload
      const tempImg = new Image();

      tempImg.onload = () => {
        img.src = src;
        if (srcset) {
          img.srcset = srcset;
        }
        img.classList.remove(config.loadingClass);
        img.classList.add(config.loadedClass);
        
        // Fade in animation
        img.style.opacity = '0';
        setTimeout(() => {
          img.style.transition = 'opacity 0.3s ease-in-out';
          img.style.opacity = '1';
        }, 10);
      };

      tempImg.onerror = () => {
        img.classList.remove(config.loadingClass);
        img.classList.add(config.errorClass);
        console.error('Failed to load image:', src);
      };

      tempImg.src = src;
      if (srcset) {
        tempImg.srcset = srcset;
      }
    }

    addPlaceholder(img) {
      // Add a placeholder background
      img.style.backgroundColor = config.placeholderColor;
      img.style.minHeight = '200px';
    }

    loadAll() {
      // Force load all images
      this.images.forEach(img => {
        this.loadImage(img);
        if (this.observer) {
          this.observer.unobserve(img);
        }
      });
    }

    refresh() {
      // Refresh and observe new images
      if (this.observer) {
        this.observer.disconnect();
      }
      this.initialize();
    }
  }

  // ============================================
  // Background Image Lazy Loader
  // ============================================
  
  class BackgroundLazyLoader {
    constructor() {
      this.elements = [];
      this.observer = null;
      this.initialize();
    }

    initialize() {
      this.elements = Array.from(document.querySelectorAll('[data-bg]'));
      
      if (this.elements.length === 0) return;

      this.observer = new IntersectionObserver(
        this.onIntersection.bind(this),
        {
          rootMargin: config.rootMargin,
          threshold: config.threshold
        }
      );

      this.elements.forEach(el => {
        this.observer.observe(el);
      });
    }

    onIntersection(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          this.loadBackground(entry.target);
          this.observer.unobserve(entry.target);
        }
      });
    }

    loadBackground(element) {
      const bgUrl = element.dataset.bg;
      
      element.classList.add(config.loadingClass);

      // Preload background image
      const img = new Image();
      img.onload = () => {
        element.style.backgroundImage = `url('${bgUrl}')`;
        element.classList.remove(config.loadingClass);
        element.classList.add(config.loadedClass);
      };

      img.onerror = () => {
        element.classList.remove(config.loadingClass);
        element.classList.add(config.errorClass);
        console.error('Failed to load background:', bgUrl);
      };

      img.src = bgUrl;
    }

    refresh() {
      if (this.observer) {
        this.observer.disconnect();
      }
      this.initialize();
    }
  }

  // ============================================
  // Iframe Lazy Loader
  // ============================================
  
  class IframeLazyLoader {
    constructor() {
      this.iframes = [];
      this.observer = null;
      this.initialize();
    }

    initialize() {
      this.iframes = Array.from(document.querySelectorAll('iframe[data-src]'));
      
      if (this.iframes.length === 0) return;

      this.observer = new IntersectionObserver(
        this.onIntersection.bind(this),
        {
          rootMargin: config.rootMargin,
          threshold: config.threshold
        }
      );

      this.iframes.forEach(iframe => {
        this.observer.observe(iframe);
      });
    }

    onIntersection(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          this.loadIframe(entry.target);
          this.observer.unobserve(entry.target);
        }
      });
    }

    loadIframe(iframe) {
      iframe.classList.add(config.loadingClass);
      iframe.src = iframe.dataset.src;
      
      iframe.onload = () => {
        iframe.classList.remove(config.loadingClass);
        iframe.classList.add(config.loadedClass);
      };

      iframe.onerror = () => {
        iframe.classList.remove(config.loadingClass);
        iframe.classList.add(config.errorClass);
      };
    }

    refresh() {
      if (this.observer) {
        this.observer.disconnect();
      }
      this.initialize();
    }
  }

  // ============================================
  // Content Lazy Loader (for AJAX content)
  // ============================================
  
  class ContentLazyLoader {
    constructor() {
      this.elements = [];
      this.observer = null;
      this.initialize();
    }

    initialize() {
      this.elements = Array.from(document.querySelectorAll('[data-lazy-content]'));
      
      if (this.elements.length === 0) return;

      this.observer = new IntersectionObserver(
        this.onIntersection.bind(this),
        {
          rootMargin: config.rootMargin,
          threshold: config.threshold
        }
      );

      this.elements.forEach(el => {
        this.observer.observe(el);
      });
    }

    onIntersection(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          this.loadContent(entry.target);
          this.observer.unobserve(entry.target);
        }
      });
    }

    async loadContent(element) {
      const url = element.dataset.lazyContent;
      
      element.classList.add(config.loadingClass);
      element.innerHTML = '<div class="loading-spinner"></div>';

      try {
        const response = await fetch(url);
        const content = await response.text();
        
        element.innerHTML = content;
        element.classList.remove(config.loadingClass);
        element.classList.add(config.loadedClass);

        // Trigger event for loaded content
        element.dispatchEvent(new CustomEvent('contentLoaded', { detail: { url } }));
      } catch (error) {
        element.classList.remove(config.loadingClass);
        element.classList.add(config.errorClass);
        element.innerHTML = '<p>Failed to load content</p>';
        console.error('Failed to load content:', error);
      }
    }

    refresh() {
      if (this.observer) {
        this.observer.disconnect();
      }
      this.initialize();
    }
  }

  // ============================================
  // Progressive Image Loading (Blur-up technique)
  // ============================================
  
  class ProgressiveImageLoader {
    constructor() {
      this.images = [];
      this.initialize();
    }

    initialize() {
      this.images = Array.from(document.querySelectorAll('img[data-src-low]'));
      
      this.images.forEach(img => {
        this.loadProgressive(img);
      });
    }

    loadProgressive(img) {
      // Load low-quality placeholder first
      const lowSrc = img.dataset.srcLow;
      const highSrc = img.dataset.src;

      // Set low-quality image
      img.src = lowSrc;
      img.style.filter = 'blur(10px)';
      img.style.transition = 'filter 0.3s ease-in-out';

      // Load high-quality image
      const highImg = new Image();
      highImg.onload = () => {
        img.src = highSrc;
        img.style.filter = 'blur(0)';
        img.classList.add(config.loadedClass);
      };
      highImg.src = highSrc;
    }
  }

  // ============================================
  // Lazy Load Manager
  // ============================================
  
  class LazyLoadManager {
    constructor() {
      this.imageLazyLoader = null;
      this.backgroundLazyLoader = null;
      this.iframeLazyLoader = null;
      this.contentLazyLoader = null;
      this.progressiveImageLoader = null;
    }

    initialize() {
      this.imageLazyLoader = new ImageLazyLoader();
      this.backgroundLazyLoader = new BackgroundLazyLoader();
      this.iframeLazyLoader = new IframeLazyLoader();
      this.contentLazyLoader = new ContentLazyLoader();
      this.progressiveImageLoader = new ProgressiveImageLoader();

      console.log('Lazy loading initialized');
    }

    refresh() {
      // Refresh all lazy loaders for dynamically added content
      this.imageLazyLoader?.refresh();
      this.backgroundLazyLoader?.refresh();
      this.iframeLazyLoader?.refresh();
      this.contentLazyLoader?.refresh();
    }

    loadAll() {
      // Force load all lazy content
      this.imageLazyLoader?.loadAll();
    }
  }

  // ============================================
  // Initialize on page load
  // ============================================
  
  const lazyLoadManager = new LazyLoadManager();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      lazyLoadManager.initialize();
    });
  } else {
    lazyLoadManager.initialize();
  }

  // Refresh on dynamic content changes
  const observer = new MutationObserver(() => {
    lazyLoadManager.refresh();
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true
  });

  // ============================================
  // Export Lazy Load API
  // ============================================
  
  window.LazyLoad = {
    manager: lazyLoadManager,
    refresh: () => lazyLoadManager.refresh(),
    loadAll: () => lazyLoadManager.loadAll(),
    config: config
  };

})();
