/**
 * Checkout page interactions — Shopify-style.
 *
 * @package StarterKit
 */
(function () {
  'use strict';

  /* ── Payment method highlight ── */

  function initPaymentHighlight() {
    var container = document.querySelector('.starterkit-checkout__payment');
    if (!container) return;

    var payments = container.querySelector('.wc_payment_methods');
    if (!payments) return;

    function refreshActive() {
      payments.querySelectorAll('.wc_payment_method').forEach(function (li) {
        li.classList.remove('active');
      });
      var checked = payments.querySelector('input[name="payment_method"]:checked');
      if (checked) {
        var parent = checked.closest('.wc_payment_method');
        if (parent) parent.classList.add('active');
      }
    }

    payments.addEventListener('change', function (e) {
      if (e.target.matches('input[name="payment_method"]')) refreshActive();
    });

    refreshActive();
  }

  /* ── Discount code (coupon) ── */

  function initDiscountCode() {
    var btn = document.getElementById('checkout_apply_coupon');
    var input = document.getElementById('checkout_coupon_code');
    if (!btn || !input) return;

    btn.addEventListener('click', function () {
      var code = input.value.trim();
      if (!code) {
        input.focus();
        return;
      }

      btn.disabled = true;
      btn.textContent = '…';

      var data = new FormData();
      data.append('action', 'starterkit_apply_coupon');
      data.append('coupon_code', code);
      data.append('security', (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.apply_coupon_nonce) || '');

      fetch(
        (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.ajax_url) ||
          (typeof woocommerce_params !== 'undefined' && woocommerce_params.ajax_url) ||
          '/wp-admin/admin-ajax.php',
        { method: 'POST', body: data, credentials: 'same-origin' }
      )
        .then(function () {
          /* Trigger WooCommerce checkout update to refresh the sidebar totals. */
          jQuery(document.body).trigger('update_checkout');
          input.value = '';
        })
        .finally(function () {
          btn.disabled = false;
          btn.textContent = 'Apply';
        });
    });

    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        btn.click();
      }
    });
  }

  /* ── Scroll to first validation error ── */

  function initErrorScroll() {
    jQuery(document.body).on('checkout_error', function () {
      var first = document.querySelector('.woocommerce-invalid, .woocommerce-error');
      if (first) {
        first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }

  /* ── Init ── */

  function init() {
    initPaymentHighlight();
    initDiscountCode();
    if (typeof jQuery !== 'undefined') {
      initErrorScroll();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  /* Re-init payment highlight after WooCommerce AJAX updates. */
  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).on('updated_checkout', function () {
      initPaymentHighlight();
    });
  }
})();
