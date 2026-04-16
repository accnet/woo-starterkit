/**
 * Shared WooCommerce Store API client for custom cart and checkout UIs.
 *
 * @package StarterKit
 */
(function () {
  'use strict';

  var config = window.starterkitCommerce || {};
  var nonce = config.nonce || '';

  function updateNonce(response) {
    var nextNonce = response.headers.get('Nonce');
    if (nextNonce) {
      nonce = nextNonce;
      config.nonce = nextNonce;
    }
  }

  function request(path, options) {
    options = options || {};

    var url = String(config.apiBase || '').replace(/\/$/, '') + path;
    var headers = new Headers(options.headers || {});
    var body = options.body;

    headers.set('Accept', 'application/json');

    if (nonce) {
      headers.set('Nonce', nonce);
    }

    if (body && !(body instanceof FormData)) {
      headers.set('Content-Type', 'application/json');
      body = JSON.stringify(body);
    }

    return fetch(url, {
      method: options.method || 'GET',
      credentials: 'same-origin',
      headers: headers,
      body: body,
    }).then(function (response) {
      updateNonce(response);

      return response.text().then(function (text) {
        var payload = text ? JSON.parse(text) : null;

        if (!response.ok) {
          var error = new Error((payload && payload.message) || 'Store API request failed.');
          error.response = payload;
          error.status = response.status;
          throw error;
        }

        return payload;
      });
    });
  }

  function get(obj, path, fallback) {
    try {
      return path.split('.').reduce(function (acc, key) {
        return acc[key];
      }, obj);
    } catch (_err) {
      return fallback;
    }
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatPrice(amount, currencyMeta) {
    if (amount == null) return '';

    var minorUnit = Number(get(currencyMeta, 'currency_minor_unit', 2));
    var prefix = get(currencyMeta, 'currency_prefix', '');
    var suffix = get(currencyMeta, 'currency_suffix', '');
    var decimal = get(currencyMeta, 'currency_decimal_separator', '.');
    var thousand = get(currencyMeta, 'currency_thousand_separator', ',');
    var value = Number(amount) / Math.pow(10, minorUnit);

    if (Number.isNaN(value)) {
      return String(amount);
    }

    var parts = value.toFixed(minorUnit).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand);

    return prefix + parts.join(decimal) + suffix;
  }

  function cartMoney(cart, key) {
    return formatPrice(get(cart, 'totals.' + key, ''), get(cart, 'totals', {}));
  }

  function getCountries() {
    return config.countries || {};
  }

  function getStates(countryCode) {
    var states = config.states || {};
    return states[countryCode] || {};
  }

  function getPaymentMethods() {
    return Array.isArray(config.paymentMethods) ? config.paymentMethods : [];
  }

  function itemImage(item) {
    var image = Array.isArray(item.images) && item.images.length ? item.images[0] : null;
    return image ? image.thumbnail || image.src || '' : '';
  }

  function itemMeta(item) {
    var parts = [];
    var variation = Array.isArray(item.variation) ? item.variation : [];
    var itemData = Array.isArray(item.item_data) ? item.item_data : [];

    variation.forEach(function (entry) {
      if (!entry) return;
      parts.push('<span><strong>' + escapeHtml(entry.attribute || '') + ':</strong> ' + escapeHtml(entry.value || '') + '</span>');
    });

    itemData.forEach(function (entry) {
      if (!entry) return;
      parts.push('<span><strong>' + escapeHtml(entry.key || '') + ':</strong> ' + escapeHtml(entry.value || '') + '</span>');
    });

    return parts.join('');
  }

  window.StarterkitCommerceStore = {
    config: config,
    request: request,
    escapeHtml: escapeHtml,
    formatPrice: formatPrice,
    cartMoney: cartMoney,
    getCountries: getCountries,
    getStates: getStates,
    getPaymentMethods: getPaymentMethods,
    itemImage: itemImage,
    itemMeta: itemMeta,
    getCart: function () {
      return request('/cart');
    },
    addItem: function (body) {
      return request('/cart/add-item', { method: 'POST', body: body });
    },
    updateItem: function (key, quantity) {
      return request('/cart/update-item', { method: 'POST', body: { key: key, quantity: quantity } });
    },
    removeItem: function (key) {
      return request('/cart/remove-item', { method: 'POST', body: { key: key } });
    },
    applyCoupon: function (code) {
      return request('/cart/apply-coupon', { method: 'POST', body: { code: code } });
    },
    removeCoupon: function (code) {
      return request('/cart/remove-coupon', { method: 'POST', body: { code: code } });
    },
    updateCustomer: function (body) {
      return request('/cart/update-customer', { method: 'POST', body: body });
    },
    selectShippingRate: function (packageId, rateId) {
      return request('/cart/select-shipping-rate', { method: 'POST', body: { package_id: packageId, rate_id: rateId } });
    },
    getCheckout: function () {
      return request('/checkout');
    },
    updateCheckout: function (body) {
      return request('/checkout?__experimental_calc_totals=true', { method: 'PUT', body: body });
    },
    checkout: function (body) {
      return request('/checkout', { method: 'POST', body: body });
    },
  };
})();
