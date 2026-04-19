/**
 * StarterKit custom checkout runtime.
 *
 * @package StarterKit
 */
(function () {
  'use strict';

  var runtime = window.starterkitCheckoutRuntime || {};

  function qs(selector, root) {
    return (root || document).querySelector(selector);
  }

  function qsa(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function cleanLabelText(text) {
    return (text || '')
      .replace(/\s*\*+\s*$/, '')
      .replace(/\s*\(optional\)\s*$/i, '')
      .trim();
  }

  function getRoot() {
    return qs('[data-checkout-root]');
  }

  function initMobileSummary() {
    var root = getRoot();
    if (!root) return;

    root.addEventListener('click', function (event) {
      var toggle = event.target.closest('[data-mobile-summary-toggle]');
      if (!toggle) return;

      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      root.classList.toggle('is-mobile-summary-open', !expanded);
    });
  }

  function initFieldPlaceholders() {
    qsa('.starterkit-checkout-details__fields .form-row').forEach(function (row) {
      var label = row.querySelector('label');
      var text = cleanLabelText(label ? label.textContent : '');
      if (!text) return;

      var input = row.querySelector('input.input-text, textarea.input-text');
      if (input && !input.getAttribute('placeholder')) {
        input.setAttribute('placeholder', text);
      }

      var select = row.querySelector('select');
      if (select) {
        if (!select.getAttribute('data-placeholder')) {
          select.setAttribute('data-placeholder', text);
        }

        var firstOption = select.options && select.options.length ? select.options[0] : null;
        if (firstOption && !firstOption.value && !firstOption.textContent.trim()) {
          firstOption.textContent = text;
        }
      }
    });
  }

  function initPaymentHighlight() {
    var payments = qs('.woocommerce-checkout-payment .wc_payment_methods');
    if (!payments) return;

    if (!payments.dataset.starterkitBound) {
      payments.addEventListener('change', function (event) {
        if (event.target.matches('input[name="payment_method"]')) {
          refreshActive();
        }
      });
      payments.dataset.starterkitBound = 'true';
    }

    function refreshActive() {
      qsa('.wc_payment_method', payments).forEach(function (item) {
        item.classList.remove('active');
      });

      var checked = payments.querySelector('input[name="payment_method"]:checked');
      var parent = checked ? checked.closest('.wc_payment_method') : null;
      if (parent) {
        parent.classList.add('active');
      }
    }

    refreshActive();
  }

  function moveShippingMethods() {
    var targetBody = qs('[data-checkout-shipping-methods-body]');
    var reviewTable = qs('.woocommerce-checkout-review-order-table');
    if (!targetBody || !reviewTable) return;

    var shippingRows = qsa('tr.shipping', reviewTable);
    targetBody.innerHTML = '';

    if (!shippingRows.length) {
      targetBody.innerHTML = '<tr><td>No shipping method is required for this order.</td></tr>';
      return;
    }

    shippingRows.forEach(function (row) {
      targetBody.appendChild(row);
    });
  }

  function initDiscountCode() {
    var btn = qs('#checkout_apply_coupon');
    var input = qs('#checkout_coupon_code');
    if (!btn || !input || btn.dataset.starterkitCouponBound) return;

    btn.dataset.starterkitCouponBound = 'true';

    btn.addEventListener('click', function () {
      var code = input.value.trim();
      if (!code) {
        input.focus();
        return;
      }

      btn.disabled = true;
      btn.textContent = (runtime.labels && runtime.labels.applying) || 'Applying...';

      var data = new FormData();
      data.append('action', 'starterkit_apply_coupon');
      data.append('coupon_code', code);
      data.append(
        'security',
        runtime.applyCouponNonce || (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.apply_coupon_nonce) || ''
      );

      fetch(
        runtime.ajaxUrl ||
          (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.ajax_url) ||
          (typeof woocommerce_params !== 'undefined' && woocommerce_params.ajax_url) ||
          '/wp-admin/admin-ajax.php',
        { method: 'POST', body: data, credentials: 'same-origin' }
      )
        .then(function () {
          input.value = '';
          if (window.jQuery) {
            window.jQuery(document.body).trigger('update_checkout');
          }
        })
        .finally(function () {
          btn.disabled = false;
          btn.textContent = (runtime.labels && runtime.labels.applyCoupon) || 'Apply';
        });
    });

    input.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        btn.click();
      }
    });
  }

  function initErrorHandling() {
    if (!window.jQuery) return;

    window.jQuery(document.body).on('checkout_error', function () {
      var first = qs('.woocommerce-invalid, .woocommerce-error');

      if (first) {
        first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }

  function rehydrate() {
    initFieldPlaceholders();
    initPaymentHighlight();
    moveShippingMethods();
  }

  function init() {
    if (!getRoot()) return;

    initMobileSummary();
    initDiscountCode();
    initErrorHandling();
    rehydrate();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  if (window.jQuery) {
    window.jQuery(document.body).on('updated_checkout', rehydrate);
  }
})();
