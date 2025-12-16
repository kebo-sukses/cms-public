/* ============================================
   CALIUS DIGITAL - CMS Functionality
   Static CMS Management System
   ============================================ */

(function() {
  'use strict';

  // ============================================
  // CMS State
  // ============================================
  
  const cmsState = {
    templates: [],
    blogPosts: [],
    orders: [],
    settings: null,
    currentUser: null,
    isEditing: false
  };

  // ============================================
  // Data Management
  // ============================================
  
  class DataManager {
    static async load(dataType) {
      try {
        const response = await fetch(`/data/${dataType}.json`);
        if (!response.ok) throw new Error(`Failed to load ${dataType}`);
        return await response.json();
      } catch (error) {
        console.error(`Error loading ${dataType}:`, error);
        return null;
      }
    }

    static async save(dataType, data) {
      try {
        // In a real implementation, this would send to a server endpoint
        // For static CMS, we'll use localStorage as a temporary solution
        // and provide instructions for manual file updates
        
        const jsonString = JSON.stringify(data, null, 2);
        localStorage.setItem(`cms_${dataType}`, jsonString);
        
        // Show instructions for manual update
        this.showSaveInstructions(dataType, jsonString);
        
        return true;
      } catch (error) {
        console.error(`Error saving ${dataType}:`, error);
        return false;
      }
    }

    static showSaveInstructions(dataType, jsonString) {
      const modal = document.createElement('div');
      modal.className = 'admin-modal-overlay active';
      modal.innerHTML = `
        <div class="admin-modal">
          <div class="admin-modal-header">
            <h3 class="admin-modal-title">Save Changes</h3>
            <button class="admin-modal-close" onclick="this.closest('.admin-modal-overlay').remove()">×</button>
          </div>
          <div class="admin-modal-body">
            <div class="admin-alert warning">
              <div class="admin-alert-icon">⚠️</div>
              <div class="admin-alert-content">
                <div class="admin-alert-title">Manual File Update Required</div>
                <p>Copy the JSON below and save it to: <code>/data/${dataType}.json</code> via cPanel File Manager</p>
              </div>
            </div>
            <textarea class="admin-form-textarea" rows="15" readonly>${jsonString}</textarea>
            <button class="btn btn-primary mt-2" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); CaliusCMS.showNotification('Copied to clipboard!', 'success')">
              📋 Copy to Clipboard
            </button>
          </div>
          <div class="admin-modal-footer">
            <button class="btn btn-secondary" onclick="this.closest('.admin-modal-overlay').remove()">Close</button>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
    }
  }

  // ============================================
  // Template Management
  // ============================================
  
  class TemplateManager {
    static async loadAll() {
      const data = await DataManager.load('templates');
      if (data) {
        cmsState.templates = data.templates || [];
      }
      return cmsState.templates;
    }

    static async create(templateData) {
      const newTemplate = {
        id: `tpl-${Date.now()}`,
        ...templateData,
        downloads: 0,
        rating: 0,
        reviews: 0,
        status: 'active',
        version: '1.0.0',
        lastUpdated: new Date().toISOString().split('T')[0]
      };

      cmsState.templates.push(newTemplate);
      await this.saveAll();
      return newTemplate;
    }

    static async update(templateId, updates) {
      const index = cmsState.templates.findIndex(t => t.id === templateId);
      if (index !== -1) {
        cmsState.templates[index] = {
          ...cmsState.templates[index],
          ...updates,
          lastUpdated: new Date().toISOString().split('T')[0]
        };
        await this.saveAll();
        return cmsState.templates[index];
      }
      return null;
    }

    static async delete(templateId) {
      cmsState.templates = cmsState.templates.filter(t => t.id !== templateId);
      await this.saveAll();
      return true;
    }

    static async saveAll() {
      let data = await DataManager.load('templates');
      if (!data || typeof data !== 'object') {
        data = { templates: [] };
      }
      data.templates = cmsState.templates;

      // Try server-side save first; fall back to manual DataManager.save
      try {
        const headers = { 'Content-Type': 'application/json' };
        if (window.CaliusCMS && window.CaliusCMS.auth && window.CaliusCMS.auth.csrfToken) headers['X-CSRF-Token'] = window.CaliusCMS.auth.csrfToken;
        const resp = await fetch('/admin/api/save-json.php', {
          method: 'POST',
          headers,
          body: JSON.stringify({ file: 'templates', data })
        });
        if (resp.ok) {
          CaliusCMS.showNotification('Templates saved to server', 'success');
          return true;
        }
      } catch (e) {
        console.warn('Server save failed, falling back to manual save', e);
      }

      return await DataManager.save('templates', data);
    }

    static getById(templateId) {
      return cmsState.templates.find(t => t.id === templateId);
    }

    static getByCategory(category) {
      return cmsState.templates.filter(t => t.category === category);
    }

    static search(query) {
      const lowerQuery = query.toLowerCase();
      return cmsState.templates.filter(t => {
        try {
          const nameEn = t?.name?.en || '';
          const nameId = t?.name?.id || '';
          const descEn = t?.description?.en || '';
          const descId = t?.description?.id || '';
          const tags = Array.isArray(t.tags) ? t.tags : [];
          return (
            nameEn.toLowerCase().includes(lowerQuery) ||
            nameId.toLowerCase().includes(lowerQuery) ||
            descEn.toLowerCase().includes(lowerQuery) ||
            descId.toLowerCase().includes(lowerQuery) ||
            tags.some(tag => (tag || '').toLowerCase().includes(lowerQuery))
          );
        } catch (e) {
          return false;
        }
      });
    }
  }

  // ============================================
  // Blog Management
  // ============================================
  
  class BlogManager {
    static async loadAll() {
      const data = await DataManager.load('blog');
      if (data) {
        cmsState.blogPosts = data.posts || [];
      }
      return cmsState.blogPosts;
    }

    static async create(postData) {
      const newPost = {
        id: `post-${Date.now()}`,
        ...postData,
        publishDate: new Date().toISOString().split('T')[0],
        lastModified: new Date().toISOString().split('T')[0],
        views: 0,
        likes: 0,
        comments: [],
        status: 'draft'
      };

      cmsState.blogPosts.push(newPost);
      await this.saveAll();
      return newPost;
    }

    static async update(postId, updates) {
      const index = cmsState.blogPosts.findIndex(p => p.id === postId);
      if (index !== -1) {
        cmsState.blogPosts[index] = {
          ...cmsState.blogPosts[index],
          ...updates,
          lastModified: new Date().toISOString().split('T')[0]
        };
        await this.saveAll();
        return cmsState.blogPosts[index];
      }
      return null;
    }

    static async delete(postId) {
      cmsState.blogPosts = cmsState.blogPosts.filter(p => p.id !== postId);
      await this.saveAll();
      return true;
    }

    static async publish(postId) {
      return await this.update(postId, { 
        status: 'published',
        publishDate: new Date().toISOString().split('T')[0]
      });
    }

    static async unpublish(postId) {
      return await this.update(postId, { status: 'draft' });
    }

    static async saveAll() {
      let data = await DataManager.load('blog');
      if (!data || typeof data !== 'object') {
        data = { posts: [] };
      }
      data.posts = cmsState.blogPosts;

      try {
        const headers = { 'Content-Type': 'application/json' };
        if (window.CaliusCMS && window.CaliusCMS.auth && window.CaliusCMS.auth.csrfToken) headers['X-CSRF-Token'] = window.CaliusCMS.auth.csrfToken;
        const resp = await fetch('/admin/api/save-json.php', {
          method: 'POST',
          headers,
          body: JSON.stringify({ file: 'blog', data })
        });
        if (resp.ok) {
          CaliusCMS.showNotification('Blog saved to server', 'success');
          return true;
        }
      } catch (e) {
        console.warn('Server save failed, falling back to manual save', e);
      }

      return await DataManager.save('blog', data);
    }

    static getById(postId) {
      return cmsState.blogPosts.find(p => p.id === postId);
    }

    static getBySlug(slug) {
      return cmsState.blogPosts.find(p => p.slug === slug);
    }

    static getByCategory(category) {
      return cmsState.blogPosts.filter(p => p.category === category);
    }

    static getPublished() {
      return cmsState.blogPosts.filter(p => p.status === 'published');
    }

    static search(query) {
      const lowerQuery = query.toLowerCase();
      return cmsState.blogPosts.filter(p => {
        try {
          const titleEn = p?.title?.en || '';
          const titleId = p?.title?.id || '';
          const excerptEn = p?.excerpt?.en || '';
          const excerptId = p?.excerpt?.id || '';
          return (
            titleEn.toLowerCase().includes(lowerQuery) ||
            titleId.toLowerCase().includes(lowerQuery) ||
            excerptEn.toLowerCase().includes(lowerQuery) ||
            excerptId.toLowerCase().includes(lowerQuery)
          );
        } catch (e) {
          return false;
        }
      });
    }
  }

  // ============================================
  // Order Management
  // ============================================
  
  class OrderManager {
    static async loadAll() {
      const data = await DataManager.load('orders');
      if (data) {
        cmsState.orders = data.orders || [];
      }
      return cmsState.orders;
    }

    static async create(orderData) {
      const orderNumber = `ORD-${new Date().toISOString().split('T')[0].replace(/-/g, '')}-${String(cmsState.orders.length + 1).padStart(3, '0')}`;
      
      const newOrder = {
        id: `order-${Date.now()}`,
        orderNumber,
        ...orderData,
        orderDate: new Date().toISOString(),
        orderStatus: 'pending',
        paymentStatus: 'pending',
        downloadStatus: 'pending',
        downloadCount: 0,
        emailSent: false
      };

      cmsState.orders.push(newOrder);
      await this.saveAll();
      return newOrder;
    }

    static async update(orderId, updates) {
      const index = cmsState.orders.findIndex(o => o.id === orderId);
      if (index !== -1) {
        cmsState.orders[index] = {
          ...cmsState.orders[index],
          ...updates
        };
        await this.saveAll();
        return cmsState.orders[index];
      }
      return null;
    }

    static async updateStatus(orderId, status) {
      return await this.update(orderId, { 
        orderStatus: status,
        completedDate: status === 'completed' ? new Date().toISOString() : null
      });
    }

    static async updatePaymentStatus(orderId, status, paymentId = null) {
      return await this.update(orderId, { 
        paymentStatus: status,
        paymentId: paymentId
      });
    }

    static async saveAll() {
      let data = await DataManager.load('orders');
      if (!data || typeof data !== 'object') {
        data = { orders: [], statistics: {} };
      }
      data.orders = cmsState.orders;

      // Update statistics
      data.statistics = this.calculateStatistics();

      try {
        const headers = { 'Content-Type': 'application/json' };
        if (window.CaliusCMS && window.CaliusCMS.auth && window.CaliusCMS.auth.csrfToken) headers['X-CSRF-Token'] = window.CaliusCMS.auth.csrfToken;
        const resp = await fetch('/admin/api/save-json.php', {
          method: 'POST',
          headers,
          body: JSON.stringify({ file: 'orders', data })
        });
        if (resp.ok) {
          CaliusCMS.showNotification('Orders saved to server', 'success');
          return true;
        }
      } catch (e) {
        console.warn('Server save failed, falling back to manual save', e);
      }

      return await DataManager.save('orders', data);
    }

    static calculateStatistics() {
      const completedOrders = cmsState.orders.filter(o => o.orderStatus === 'completed');
      
      return {
        totalOrders: cmsState.orders.length,
        totalRevenue: completedOrders.reduce((sum, o) => sum + o.total, 0),
        totalCustomers: new Set(cmsState.orders.map(o => o.customer.email)).size,
        averageOrderValue: completedOrders.length > 0 
          ? completedOrders.reduce((sum, o) => sum + o.total, 0) / completedOrders.length 
          : 0,
        conversionRate: 0, // Would need visitor data
        topSellingTemplates: this.getTopSellingTemplates(),
        revenueByMonth: this.getRevenueByMonth(),
        ordersByCategory: this.getOrdersByCategory(),
        paymentMethods: this.getPaymentMethodStats()
      };
    }

    static getTopSellingTemplates() {
      const templateCounts = {};
      cmsState.orders.forEach(order => {
        order.items.forEach(item => {
          templateCounts[item.templateId] = (templateCounts[item.templateId] || 0) + 1;
        });
      });
      
      return Object.entries(templateCounts)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 5)
        .map(([id, count]) => ({ templateId: id, count }));
    }

    static getRevenueByMonth() {
      const revenueByMonth = {};
      cmsState.orders
        .filter(o => o.orderStatus === 'completed')
        .forEach(order => {
          const month = order.orderDate.substring(0, 7); // YYYY-MM
          revenueByMonth[month] = (revenueByMonth[month] || 0) + order.total;
        });
      return revenueByMonth;
    }

    static getOrdersByCategory() {
      const categories = {
        business: 0,
        ecommerce: 0,
        portfolio: 0,
        'landing-page': 0,
        restaurant: 0
      };
      
      cmsState.orders.forEach(order => {
        order.items.forEach(item => {
          const template = TemplateManager.getById(item.templateId);
          if (template && categories.hasOwnProperty(template.category)) {
            categories[template.category]++;
          }
        });
      });
      
      return categories;
    }

    static getPaymentMethodStats() {
      const methods = { stripe: 0, paypal: 0, midtrans: 0 };
      cmsState.orders
        .filter(o => o.paymentStatus === 'completed')
        .forEach(order => {
          if (methods.hasOwnProperty(order.paymentMethod)) {
            methods[order.paymentMethod]++;
          }
        });
      return methods;
    }

    static getById(orderId) {
      return cmsState.orders.find(o => o.id === orderId);
    }

    static getByCustomerEmail(email) {
      return cmsState.orders.filter(o => o.customer.email === email);
    }

    static getRecent(limit = 10) {
      return [...cmsState.orders]
        .sort((a, b) => new Date(b.orderDate) - new Date(a.orderDate))
        .slice(0, limit);
    }
  }

  // ============================================
  // Settings Management
  // ============================================
  
  class SettingsManager {
    static async load() {
      const data = await DataManager.load('settings');
      if (data) {
        cmsState.settings = data;
      }
      return cmsState.settings;
    }

    static async update(updates) {
      cmsState.settings = {
        ...cmsState.settings,
        ...updates,
        lastUpdated: new Date().toISOString()
      };
      return await DataManager.save('settings', cmsState.settings);
    }

    static get(key) {
      return key.split('.').reduce((obj, k) => obj?.[k], cmsState.settings);
    }

    static async set(key, value) {
      if (!cmsState.settings || typeof cmsState.settings !== 'object') {
        cmsState.settings = {};
      }
      const keys = key.split('.');
      const lastKey = keys.pop();
      const target = keys.reduce((obj, k) => obj[k] = obj[k] || {}, cmsState.settings);
      target[lastKey] = value;
      return await this.update(cmsState.settings);
    }
  }

  // ============================================
  // File Upload Handler
  // ============================================
  
  class FileUploader {
    static async upload(file, type = 'image') {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
          // In a real implementation, this would upload to server
          // For now, we'll use data URL
          const dataUrl = e.target.result;
          
          // Show instructions for manual upload
          const modal = document.createElement('div');
          modal.className = 'admin-modal-overlay active';
          modal.innerHTML = `
            <div class="admin-modal">
              <div class="admin-modal-header">
                <h3 class="admin-modal-title">Upload File</h3>
                <button class="admin-modal-close" onclick="this.closest('.admin-modal-overlay').remove()">×</button>
              </div>
              <div class="admin-modal-body">
                <div class="admin-alert info">
                  <div class="admin-alert-icon">ℹ️</div>
                  <div class="admin-alert-content">
                    <div class="admin-alert-title">Manual Upload Required</div>
                    <p>Please upload this file to your server via cPanel File Manager:</p>
                    <p><strong>File:</strong> ${file.name}</p>
                    <p><strong>Suggested path:</strong> /assets/images/${type}s/${file.name}</p>
                  </div>
                </div>
                <img src="${dataUrl}" style="max-width: 100%; margin-top: 1rem;" />
              </div>
              <div class="admin-modal-footer">
                <button class="btn btn-primary" onclick="this.closest('.admin-modal-overlay').remove()">OK</button>
              </div>
            </div>
          `;
          document.body.appendChild(modal);
          
              resolve(`/assets/images/${type}s/${file.name}`);
        };
        
        reader.onerror = reject;
        reader.readAsDataURL(file);
      });
    }
  }

  // ============================================
  // Notification System
  // ============================================
  
  function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `admin-alert ${type}`;
    notification.setAttribute('role', 'status');
    notification.innerHTML = `
      <div class="admin-alert-icon">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</div>
      <div class="admin-alert-content">${message}</div>
    `;
    
    const container = document.querySelector('.admin-content') || document.body;
    container.insertBefore(notification, container.firstChild);

    // announce to screen readers via global aria-live region if present
    const aria = document.getElementById('aria-notify');
    if (aria) aria.textContent = message;

    // animate in
    requestAnimationFrame(() => notification.classList.add('show'));

    // remove after timeout (respect reduced motion)
    const timeout = 3000;
    setTimeout(() => {
      notification.classList.remove('show');
      setTimeout(() => notification.remove(), 300);
    }, timeout);
  }

  // ============================================
  // Export CMS API
  // ============================================
  
  window.CaliusCMS = {
    state: cmsState,
    DataManager,
    TemplateManager,
    BlogManager,
    OrderManager,
    SettingsManager,
    FileUploader,
    showNotification
  };

  console.log('Calius CMS initialized');

  // Apply site settings (branding) on load
  (async function applyBranding() {
    try {
      const settings = await SettingsManager.load();
      const brand = settings?.site?.brandColor || settings?.site?.brand || null;
      if (brand) {
        // set brand CSS variable and update primary color for consistency
        document.documentElement.style.setProperty('--brand-color', brand);
        document.documentElement.style.setProperty('--primary-color', brand);
        // compute contrast color (white for dark colors) and rgba components
        const hex = brand.replace('#', '').padEnd(6, '0');
        const r = parseInt(hex.substring(0,2),16);
        const g = parseInt(hex.substring(2,4),16);
        const b = parseInt(hex.substring(4,6),16);
        const l = (0.299*r + 0.587*g + 0.114*b)/255;
        const contrast = l > 0.6 ? '#111' : '#fff';
        document.documentElement.style.setProperty('--brand-contrast', contrast);
        document.documentElement.style.setProperty('--brand-rgba', `${r}, ${g}, ${b}`);
        // Update admin logo images if present
        const logoUrl = settings?.site?.logo || settings?.site?.logoUrl || null;
        if (logoUrl) {
          const imgEls = document.querySelectorAll('.admin-logo img, #current-logo-img');
          imgEls.forEach(img => { try { img.src = logoUrl; } catch(e){} });
          // If .admin-logo is a text node, replace with image
          const logoText = document.querySelector('.admin-sidebar .admin-logo');
          if (logoText && !logoText.querySelector('img')) {
            // keep existing text as alt
            const alt = logoText.textContent.trim() || 'Logo';
            logoText.innerHTML = `<img src="${logoUrl}" alt="${alt}" style="height:36px;" />`;
          }
        }
      }
    } catch (e) {
      // ignore
    }
  })();

})();
