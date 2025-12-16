/* ============================================
   CALIUS DIGITAL - Payment Integration
   Stripe, PayPal, and Midtrans Support
   ============================================ */

(function() {
  'use strict';

  // ============================================
  // Payment State
  // ============================================
  
  const paymentState = {
    selectedMethod: null,
    orderData: null,
    processing: false,
    settings: null
  };

  // ============================================
  // Payment Manager
  // ============================================
  
  class PaymentManager {
    static async initialize() {
      try {
        // Load payment settings
        const response = await fetch('/data/settings.json');
        const data = await response.json();
        paymentState.settings = data.payment;

        // Initialize payment gateways
        if (paymentState.settings.stripe.enabled) {
          await this.initializeStripe();
        }
        if (paymentState.settings.paypal.enabled) {
          await this.initializePayPal();
        }
        if (paymentState.settings.midtrans.enabled) {
          await this.initializeMidtrans();
        }

        console.log('Payment gateways initialized');
      } catch (error) {
        console.error('Payment initialization error:', error);
      }
    }

    static async initializeStripe() {
      // Load Stripe.js
      if (!window.Stripe) {
        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.async = true;
        document.head.appendChild(script);

        await new Promise((resolve) => {
          script.onload = resolve;
        });
      }

      // Initialize Stripe
      if (paymentState.settings.stripe.publicKey) {
        window.stripeInstance = Stripe(paymentState.settings.stripe.publicKey);
        console.log('Stripe initialized');
      }
    }

    static async initializePayPal() {
      // Load PayPal SDK
      if (!window.paypal) {
        const script = document.createElement('script');
        script.src = `https://www.paypal.com/sdk/js?client-id=${paymentState.settings.paypal.clientId}&currency=${paymentState.settings.paypal.currency}`;
        script.async = true;
        document.head.appendChild(script);

        await new Promise((resolve) => {
          script.onload = resolve;
        });
      }

      console.log('PayPal initialized');
    }

    static async initializeMidtrans() {
      // Load Midtrans Snap
      if (!window.snap) {
        const script = document.createElement('script');
        script.src = paymentState.settings.midtrans.environment === 'production'
          ? 'https://app.midtrans.com/snap/snap.js'
          : 'https://app.sandbox.midtrans.com/snap/snap.js';
        script.setAttribute('data-client-key', paymentState.settings.midtrans.clientKey);
        script.async = true;
        document.head.appendChild(script);

        await new Promise((resolve) => {
          script.onload = resolve;
        });
      }

      console.log('Midtrans initialized');
    }

    static selectPaymentMethod(method) {
      paymentState.selectedMethod = method;
      
      // Update UI
      document.querySelectorAll('.payment-method-option').forEach(option => {
        option.classList.toggle('active', option.dataset.method === method);
      });

      // Show/hide payment forms
      document.querySelectorAll('.payment-form').forEach(form => {
        form.style.display = form.dataset.method === method ? 'block' : 'none';
      });
    }

    static async processPayment(orderData) {
      if (!paymentState.selectedMethod) {
        throw new Error('Please select a payment method');
      }

      if (paymentState.processing) {
        return;
      }

      paymentState.processing = true;
      paymentState.orderData = orderData;

      try {
        let result;

        switch (paymentState.selectedMethod) {
          case 'stripe':
            result = await StripePayment.process(orderData);
            break;
          case 'paypal':
            result = await PayPalPayment.process(orderData);
            break;
          case 'midtrans':
            result = await MidtransPayment.process(orderData);
            break;
          default:
            throw new Error('Invalid payment method');
        }

        return result;
      } catch (error) {
        console.error('Payment processing error:', error);
        throw error;
      } finally {
        paymentState.processing = false;
      }
    }
  }

  // ============================================
  // Stripe Payment Handler
  // ============================================
  
  class StripePayment {
    static async process(orderData) {
      try {
        // Create payment intent on server (simulated)
        const paymentIntent = await this.createPaymentIntent(orderData);

        // Confirm payment with Stripe
        const { error, paymentIntent: confirmedIntent } = await window.stripeInstance.confirmCardPayment(
          paymentIntent.clientSecret,
          {
            payment_method: {
              card: this.getCardElement(),
              billing_details: {
                name: `${orderData.customer.firstName} ${orderData.customer.lastName}`,
                email: orderData.customer.email,
                phone: orderData.customer.phone
              }
            }
          }
        );

        if (error) {
          throw new Error(error.message);
        }

        if (confirmedIntent.status === 'succeeded') {
          return {
            success: true,
            paymentId: confirmedIntent.id,
            method: 'stripe',
            status: 'completed'
          };
        }

        throw new Error('Payment failed');
      } catch (error) {
        console.error('Stripe payment error:', error);
        throw error;
      }
    }

    static async createPaymentIntent(orderData) {
      // In production, this would call your server endpoint
      // For demo, we'll simulate the response
      return {
        clientSecret: 'pi_test_secret_' + Math.random().toString(36).substring(7),
        id: 'pi_' + Math.random().toString(36).substring(7)
      };
    }

    static getCardElement() {
      // Get Stripe card element
      // In production, you would have created this element earlier
      return document.getElementById('stripe-card-element');
    }

    static createCardElement(elementId) {
      if (!window.stripeInstance) {
        console.error('Stripe not initialized');
        return;
      }

      const elements = window.stripeInstance.elements();
      const cardElement = elements.create('card', {
        style: {
          base: {
            fontSize: '16px',
            color: '#32325d',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            '::placeholder': {
              color: '#aab7c4'
            }
          },
          invalid: {
            color: '#ef4444',
            iconColor: '#ef4444'
          }
        }
      });

      cardElement.mount(`#${elementId}`);
      return cardElement;
    }
  }

  // ============================================
  // PayPal Payment Handler
  // ============================================
  
  class PayPalPayment {
    static async process(orderData) {
      return new Promise((resolve, reject) => {
        if (!window.paypal) {
          reject(new Error('PayPal SDK not loaded'));
          return;
        }

        // Render PayPal button
        window.paypal.Buttons({
          createOrder: (data, actions) => {
            return actions.order.create({
              purchase_units: [{
                amount: {
                  value: orderData.total.toFixed(2),
                  currency_code: paymentState.settings.paypal.currency
                },
                description: 'Calius Digital Template Purchase',
                custom_id: orderData.orderId || 'order_' + Date.now()
              }]
            });
          },
          onApprove: async (data, actions) => {
            const order = await actions.order.capture();
            resolve({
              success: true,
              paymentId: order.id,
              method: 'paypal',
              status: 'completed',
              details: order
            });
          },
          onError: (err) => {
            console.error('PayPal error:', err);
            reject(new Error('PayPal payment failed'));
          },
          onCancel: () => {
            reject(new Error('Payment cancelled by user'));
          }
        }).render('#paypal-button-container');
      });
    }
  }

  // ============================================
  // Midtrans Payment Handler
  // ============================================
  
  class MidtransPayment {
    static async process(orderData) {
      try {
        // Get snap token from server (simulated)
        const snapToken = await this.getSnapToken(orderData);

        return new Promise((resolve, reject) => {
          if (!window.snap) {
            reject(new Error('Midtrans Snap not loaded'));
            return;
          }

          window.snap.pay(snapToken, {
            onSuccess: (result) => {
              resolve({
                success: true,
                paymentId: result.transaction_id,
                method: 'midtrans',
                status: 'completed',
                details: result
              });
            },
            onPending: (result) => {
              resolve({
                success: true,
                paymentId: result.transaction_id,
                method: 'midtrans',
                status: 'pending',
                details: result
              });
            },
            onError: (result) => {
              reject(new Error('Midtrans payment failed: ' + result.status_message));
            },
            onClose: () => {
              reject(new Error('Payment popup closed'));
            }
          });
        });
      } catch (error) {
        console.error('Midtrans payment error:', error);
        throw error;
      }
    }

    static async getSnapToken(orderData) {
      // In production, this would call your server endpoint
      // For demo, we'll simulate the response
      
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 500));
      
      return 'snap_token_' + Math.random().toString(36).substring(7);
    }
  }

  // ============================================
  // Order Creation
  // ============================================
  
  class OrderCreator {
    static async createOrder(cartItems, customerData, paymentResult) {
      const order = {
        id: 'order-' + Date.now(),
        orderNumber: this.generateOrderNumber(),
        customer: customerData,
        items: cartItems.map(item => ({
          templateId: item.id,
          templateName: item.name,
          price: item.price,
          quantity: item.quantity || 1,
          downloadUrl: `/downloads/${item.id}.zip`
        })),
        subtotal: this.calculateSubtotal(cartItems),
        tax: 0,
        discount: 0,
        total: this.calculateTotal(cartItems),
        currency: paymentState.settings[paymentResult.method].currency,
        paymentMethod: paymentResult.method,
        paymentStatus: paymentResult.status,
        paymentId: paymentResult.paymentId,
        orderStatus: paymentResult.status === 'completed' ? 'completed' : 'pending',
        downloadStatus: paymentResult.status === 'completed' ? 'available' : 'pending',
        downloadCount: 0,
        downloadLimit: 5,
        downloadExpiry: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString(), // 1 year
        orderDate: new Date().toISOString(),
        completedDate: paymentResult.status === 'completed' ? new Date().toISOString() : null,
        ipAddress: await this.getClientIP(),
        userAgent: navigator.userAgent,
        notes: '',
        refunded: false,
        emailSent: false
      };

      // Save order (in production, send to server)
      await this.saveOrder(order);

      return order;
    }

    static generateOrderNumber() {
      const date = new Date();
      const dateStr = date.toISOString().split('T')[0].replace(/-/g, '');
      const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
      return `ORD-${dateStr}-${random}`;
    }

    static calculateSubtotal(items) {
      return items.reduce((sum, item) => sum + (item.price * (item.quantity || 1)), 0);
    }

    static calculateTotal(items) {
      // Add tax, discounts, etc. here if needed
      return this.calculateSubtotal(items);
    }

    static async getClientIP() {
      try {
        const response = await fetch('https://api.ipify.org?format=json');
        const data = await response.json();
        return data.ip;
      } catch (error) {
        return 'unknown';
      }
    }

    static async saveOrder(order) {
      // In production, send to server
      // For now, save to localStorage
      const orders = JSON.parse(localStorage.getItem('calius_orders') || '[]');
      orders.push(order);
      localStorage.setItem('calius_orders', JSON.stringify(orders));

      // Show save instructions for manual update
      console.log('Order created:', order);
      return order;
    }
  }

  // ============================================
  // Checkout Form Handler
  // ============================================
  
  function initializeCheckoutForm() {
    const checkoutForm = document.getElementById('checkout-form');
    if (!checkoutForm) return;

    checkoutForm.addEventListener('submit', async function(e) {
      e.preventDefault();

      const formData = new FormData(checkoutForm);
      const customerData = {
        email: formData.get('email'),
        firstName: formData.get('firstName'),
        lastName: formData.get('lastName'),
        country: formData.get('country'),
        phone: formData.get('phone')
      };

      // Get cart items
      const cart = JSON.parse(localStorage.getItem('cart') || '[]');
      if (cart.length === 0) {
        alert('Your cart is empty');
        return;
      }

      const submitBtn = checkoutForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;

      try {
        // Disable button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading"></span> Processing...';

        // Process payment
        const paymentResult = await PaymentManager.processPayment({
          customer: customerData,
          items: cart,
          total: cart.reduce((sum, item) => sum + item.price, 0)
        });

        // Create order
        const order = await OrderCreator.createOrder(cart, customerData, paymentResult);

        // Clear cart
        localStorage.removeItem('cart');

        // Redirect to success page
        window.location.href = `/order-success.html?order=${order.orderNumber}`;

      } catch (error) {
        console.error('Checkout error:', error);
        alert('Payment failed: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    });

    // Payment method selection
    document.querySelectorAll('.payment-method-option').forEach(option => {
      option.addEventListener('click', function() {
        PaymentManager.selectPaymentMethod(this.dataset.method);
      });
    });
  }

  // ============================================
  // Initialize on page load
  // ============================================
  
  document.addEventListener('DOMContentLoaded', async function() {
    await PaymentManager.initialize();
    initializeCheckoutForm();

    // Initialize Stripe card element if on checkout page
    if (document.getElementById('stripe-card-element')) {
      StripePayment.createCardElement('stripe-card-element');
    }
  });

  // ============================================
  // Export Payment API
  // ============================================
  
  window.CaliusPayment = {
    state: paymentState,
    PaymentManager,
    StripePayment,
    PayPalPayment,
    MidtransPayment,
    OrderCreator
  };

  console.log('Payment system initialized');

})();
