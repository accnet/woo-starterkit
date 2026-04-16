(function () {
  'use strict';

  var store = window.StarterkitCommerceStore;
  var config = window.starterkitCommerce || {};

  if (!store) {
    return;
  }

  var state = {
    cart: null,
    checkout: null,
    busy: false,
    syncTimer: null,
  };

  function i18n(key, fallback) {
    return (config.i18n && config.i18n[key]) || fallback || '';
  }

  function formRoot() {
    return document.getElementById('starterkit-checkout-form-root');
  }

  function summaryRoot() {
    return document.getElementById('starterkit-checkout-summary-root');
  }

  function noticeRoot() {
    return document.querySelector('[data-checkout-notice]');
  }

  function escape(value) {
    return store.escapeHtml(value);
  }

  function setNotice(message, isError) {
    var node = noticeRoot();

    if (!node) {
      return;
    }

    node.textContent = message || '';
    node.className = 'starterkit-checkout-app__notice' + (message ? ' is-visible' : '') + (isError ? ' is-error' : '');
  }

  function setBusy(busy) {
    state.busy = busy;
    document.documentElement.classList.toggle('starterkit-checkout-busy', busy);
  }

  function emitCartUpdated(cart) {
    document.dispatchEvent(new CustomEvent('starterkit:cart-updated', {
      detail: {
        cart: cart,
      },
    }));
  }

  function renderLoading() {
    if (formRoot()) {
      formRoot().innerHTML = '<div class="starterkit-checkout-app__loading">' + escape(i18n('loadingCheckout', 'Preparing checkout...')) + '</div>';
    }

    if (summaryRoot()) {
      summaryRoot().innerHTML = '<div class="starterkit-checkout-app__loading">' + escape(i18n('loadingCheckout', 'Preparing checkout...')) + '</div>';
    }
  }

  function isSameAddress(billing, shipping) {
    var keys = ['first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country'];
    return keys.every(function (key) {
      return String((billing && billing[key]) || '') === String((shipping && shipping[key]) || '');
    });
  }

  function getCountriesOptions(selected) {
    return Object.keys(config.countries || {}).map(function (code) {
      return '<option value="' + escape(code) + '"' + (String(selected || '') === code ? ' selected' : '') + '>' + escape(config.countries[code]) + '</option>';
    }).join('');
  }

  function getStateField(prefix, country, selected) {
    var states = store.getStates(country || '');
    var codes = Object.keys(states || {});

    if (codes.length) {
      return '' +
        '<select name="' + prefix + '_state" autocomplete="address-level1">' +
          '<option value="">' + escape(i18n('selectOption', 'Select')) + '</option>' +
          codes.map(function (code) {
            return '<option value="' + escape(code) + '"' + (String(selected || '') === code ? ' selected' : '') + '>' + escape(states[code]) + '</option>';
          }).join('') +
        '</select>';
    }

    return '<input type="text" name="' + prefix + '_state" value="' + escape(selected || '') + '" autocomplete="address-level1">';
  }

  function addressFields(prefix, address, includeEmailPhone) {
    return '' +
      '<div class="starterkit-checkout-app__fields">' +
        (includeEmailPhone ? '<label><span>' + escape(i18n('email', 'Email')) + '</span><input type="email" name="' + prefix + '_email" value="' + escape(address.email || '') + '" autocomplete="email" required></label>' : '') +
        (includeEmailPhone ? '<label><span>' + escape(i18n('phone', 'Phone')) + '</span><input type="tel" name="' + prefix + '_phone" value="' + escape(address.phone || '') + '" autocomplete="tel"></label>' : '') +
        '<label><span>' + escape(i18n('firstName', 'First name')) + '</span><input type="text" name="' + prefix + '_first_name" value="' + escape(address.first_name || '') + '" autocomplete="given-name" required></label>' +
        '<label><span>' + escape(i18n('lastName', 'Last name')) + '</span><input type="text" name="' + prefix + '_last_name" value="' + escape(address.last_name || '') + '" autocomplete="family-name" required></label>' +
        '<label class="is-full"><span>' + escape(i18n('company', 'Company')) + '</span><input type="text" name="' + prefix + '_company" value="' + escape(address.company || '') + '" autocomplete="organization"></label>' +
        '<label class="is-full"><span>' + escape(i18n('address1', 'Address line 1')) + '</span><input type="text" name="' + prefix + '_address_1" value="' + escape(address.address_1 || '') + '" autocomplete="address-line1" required></label>' +
        '<label class="is-full"><span>' + escape(i18n('address2', 'Address line 2')) + '</span><input type="text" name="' + prefix + '_address_2" value="' + escape(address.address_2 || '') + '" autocomplete="address-line2"></label>' +
        '<label><span>' + escape(i18n('city', 'City')) + '</span><input type="text" name="' + prefix + '_city" value="' + escape(address.city || '') + '" autocomplete="address-level2" required></label>' +
        '<label><span>' + escape(i18n('postcode', 'Postcode')) + '</span><input type="text" name="' + prefix + '_postcode" value="' + escape(address.postcode || '') + '" autocomplete="postal-code" required></label>' +
        '<label><span>' + escape(i18n('country', 'Country / Region')) + '</span><select name="' + prefix + '_country" autocomplete="country" required><option value="">' + escape(i18n('selectOption', 'Select')) + '</option>' + getCountriesOptions(address.country || '') + '</select></label>' +
        '<label><span>' + escape(i18n('state', 'State / Province')) + '</span>' + getStateField(prefix, address.country || '', address.state || '') + '</label>' +
      '</div>';
  }

  function renderShippingRates(cart) {
    var packages = Array.isArray(cart.shipping_rates) ? cart.shipping_rates : [];

    if (!packages.length) {
      return '<p class="starterkit-checkout-app__muted">' + escape(i18n('shippingPending', 'Enter shipping address')) + '</p>';
    }

    return packages.map(function (pkg, packageIndex) {
      return '<div class="starterkit-checkout-app__rates">' + (pkg.shipping_rates || []).map(function (rate) {
        var id = 'checkout-shipping-' + packageIndex + '-' + escape(rate.rate_id);
        return '' +
          '<label class="starterkit-checkout-app__rate" for="' + id + '">' +
            '<input id="' + id + '" type="radio" name="checkout_shipping_' + packageIndex + '" value="' + escape(rate.rate_id) + '" data-package-id="' + escape(pkg.package_id) + '" ' + (rate.selected ? 'checked' : '') + '>' +
            '<span><strong>' + escape(rate.name) + '</strong><small>' + escape(rate.description || '') + '</small></span>' +
            '<span>' + escape(store.formatPrice(rate.price, cart.totals)) + '</span>' +
          '</label>';
      }).join('') + '</div>';
    }).join('');
  }

  function renderPaymentMethods(selectedId) {
    var methods = store.getPaymentMethods();

    if (!methods.length) {
      return '<p class="starterkit-checkout-app__muted">' + escape(i18n('paymentUnavailable', 'No payment methods are currently available for this order.')) + '</p>';
    }

    return '' +
      '<div class="starterkit-checkout-app__payments">' +
        methods.map(function (method, index) {
          var checked = selectedId ? selectedId === method.id : index === 0;
          var unsupported = !!method.has_fields;
          return '' +
            '<label class="starterkit-checkout-app__payment">' +
              '<input type="radio" name="payment_method" value="' + escape(method.id) + '" ' + (checked ? 'checked' : '') + '>' +
              '<span class="starterkit-checkout-app__payment-copy">' +
                '<strong>' + escape(method.title) + '</strong>' +
                (method.description ? '<small>' + escape(method.description) + '</small>' : '') +
                (unsupported ? '<small class="is-warning">' + escape(i18n('paymentUnsupported', 'This gateway needs extra fields that are not exposed in the custom checkout yet.')) + '</small>' : '') +
              '</span>' +
            '</label>';
        }).join('') +
      '</div>';
  }

  function renderForm(cart, checkout) {
    if (!formRoot()) {
      return;
    }

    var shippingAddress = checkout.shipping_address || {};
    var billingAddress = checkout.billing_address || {};
    var sameAsShipping = isSameAddress(billingAddress, shippingAddress);

    formRoot().innerHTML = '' +
      '<form class="starterkit-checkout-app__form" data-custom-checkout-form>' +
        '<section class="starterkit-checkout-app__section starterkit-checkout-app__section--plain">' +
          '<div class="starterkit-checkout-app__section-head"><h2>' + escape(i18n('contact', 'Contact')) + '</h2></div>' +
          '<div class="starterkit-checkout-app__fields">' +
            '<label class="is-full"><span>' + escape(i18n('email', 'Email')) + '</span><input type="email" name="billing_email" placeholder="Email or mobile phone number" value="' + escape(billingAddress.email || '') + '" autocomplete="email" required></label>' +
            '<label class="is-full"><span>' + escape(i18n('phone', 'Phone')) + '</span><input type="tel" name="billing_phone" value="' + escape(billingAddress.phone || '') + '" autocomplete="tel"></label>' +
          '</div>' +
        '</section>' +
        (cart.needs_shipping ? '' +
          '<section class="starterkit-checkout-app__section starterkit-checkout-app__section--plain">' +
            '<div class="starterkit-checkout-app__section-head"><h2>' + escape(i18n('delivery', 'Delivery')) + '</h2></div>' +
            '<div data-shipping-address-fields>' + addressFields('shipping', shippingAddress, false) + '</div>' +
            '<label class="starterkit-checkout-app__checkbox"><input type="checkbox" name="save_shipping_information"><span>Save this information for next time</span></label>' +
            '<div class="starterkit-checkout-app__shipping-rates">' + renderShippingRates(cart) + '</div>' +
          '</section>' : '') +
        '<section class="starterkit-checkout-app__section starterkit-checkout-app__section--plain">' +
          '<div class="starterkit-checkout-app__section-head"><h2>' + escape(i18n('billingAddress', 'Billing address')) + '</h2></div>' +
          (cart.needs_shipping ? '<label class="starterkit-checkout-app__checkbox"><input type="checkbox" name="billing_same_as_shipping" ' + (sameAsShipping ? 'checked' : '') + '><span>' + escape(i18n('sameAsShipping', 'Billing address is the same as shipping')) + '</span></label>' : '') +
          '<div data-billing-address-fields ' + ((cart.needs_shipping && sameAsShipping) ? 'hidden' : '') + '>' + addressFields('billing', billingAddress, false) + '</div>' +
        '</section>' +
        '<section class="starterkit-checkout-app__section starterkit-checkout-app__section--payment">' +
          '<div class="starterkit-checkout-app__section-head"><h2>' + escape(i18n('payment', 'Payment')) + '</h2></div>' +
          '<p class="starterkit-checkout-app__muted">All transactions are secure and encrypted.</p>' +
          renderPaymentMethods(checkout.payment_method || '') +
          '<label class="is-full starterkit-checkout-app__notes"><span>' + escape(i18n('orderNotes', 'Order notes')) + '</span><textarea name="customer_note" rows="4">' + escape(checkout.customer_note || '') + '</textarea></label>' +
        '</section>' +
        '<button type="submit" class="button button-primary starterkit-checkout-app__submit">' + escape(i18n('placeOrder', 'Place order')) + '</button>' +
      '</form>';
  }

  function renderSummary(cart) {
    if (!summaryRoot()) {
      return;
    }

    var totals = cart.totals || {};
    var coupons = Array.isArray(cart.coupons) ? cart.coupons : [];
    var shippingText = Number(totals.total_shipping || 0) > 0
      ? escape(store.cartMoney(cart, 'total_shipping'))
      : '<span class="starterkit-checkout-app__shipping-pending">' + escape(i18n('shippingPending', 'Enter shipping address')) + '</span>';

    summaryRoot().innerHTML = '' +
      '<div class="starterkit-checkout-app__order-summary">' +
        '<div class="starterkit-checkout-app__summary-items">' +
          (cart.items || []).map(function (item) {
            var image = store.itemImage(item);
            return '' +
              '<div class="starterkit-checkout-app__summary-item">' +
                '<div class="starterkit-checkout-app__summary-thumb">' + (image ? '<img src="' + escape(image) + '" alt="' + escape(item.name) + '">' : '') + '<span>' + escape(item.quantity) + '</span></div>' +
                '<div class="starterkit-checkout-app__summary-copy"><strong>' + escape(item.name) + '</strong><small>' + store.itemMeta(item) + '</small></div>' +
                '<div class="starterkit-checkout-app__summary-price">' + escape(store.formatPrice(item.totals.line_total, cart.totals)) + '</div>' +
              '</div>';
          }).join('') +
        '</div>' +
        '<form class="starterkit-checkout-app__coupon" data-checkout-coupon-form>' +
          '<input type="text" name="coupon_code" placeholder="' + escape(i18n('discountCode', 'Discount code')) + '">' +
          '<button type="submit">' + escape(i18n('apply', 'Apply')) + '</button>' +
        '</form>' +
        (coupons.length ? '<div class="starterkit-checkout-app__coupon-tags">' + coupons.map(function (coupon) {
          return '<button type="button" class="starterkit-checkout-app__coupon-tag" data-remove-coupon="' + escape(coupon.code) + '">' + escape(coupon.code) + ' <span aria-hidden="true">&times;</span></button>';
        }).join('') + '</div>' : '') +
        '<div class="starterkit-checkout-app__totals">' +
          '<div><span>' + escape(i18n('subtotal', 'Subtotal')) + '</span><strong>' + escape(store.cartMoney(cart, 'total_items')) + '</strong></div>' +
          (coupons.map(function (coupon) {
            return '<div><span>' + escape(i18n('discount', 'Discount')) + ' · ' + escape(coupon.code) + '</span><strong>-' + escape(store.formatPrice(coupon.totals.total_discount, totals)) + '</strong></div>';
          }).join('')) +
          '<div><span>' + escape(i18n('shipping', 'Shipping')) + '</span><strong>' + shippingText + '</strong></div>' +
          '<div class="is-total"><span>' + escape(i18n('total', 'Total')) + '</span><strong>' + escape(store.cartMoney(cart, 'total_price')) + '</strong></div>' +
        '</div>' +
      '</div>';
  }

  function getForm() {
    return document.querySelector('[data-custom-checkout-form]');
  }

  function collectAddress(form, prefix, existing) {
    return {
      first_name: form.elements[prefix + '_first_name'] ? form.elements[prefix + '_first_name'].value.trim() : (existing.first_name || ''),
      last_name: form.elements[prefix + '_last_name'] ? form.elements[prefix + '_last_name'].value.trim() : (existing.last_name || ''),
      company: form.elements[prefix + '_company'] ? form.elements[prefix + '_company'].value.trim() : (existing.company || ''),
      address_1: form.elements[prefix + '_address_1'] ? form.elements[prefix + '_address_1'].value.trim() : (existing.address_1 || ''),
      address_2: form.elements[prefix + '_address_2'] ? form.elements[prefix + '_address_2'].value.trim() : (existing.address_2 || ''),
      city: form.elements[prefix + '_city'] ? form.elements[prefix + '_city'].value.trim() : (existing.city || ''),
      state: form.elements[prefix + '_state'] ? form.elements[prefix + '_state'].value.trim() : (existing.state || ''),
      postcode: form.elements[prefix + '_postcode'] ? form.elements[prefix + '_postcode'].value.trim() : (existing.postcode || ''),
      country: form.elements[prefix + '_country'] ? form.elements[prefix + '_country'].value.trim() : (existing.country || ''),
      phone: prefix === 'billing' ? (form.elements.billing_phone ? form.elements.billing_phone.value.trim() : (existing.phone || '')) : (existing.phone || ''),
      email: prefix === 'billing' ? (form.elements.billing_email ? form.elements.billing_email.value.trim() : (existing.email || '')) : (existing.email || ''),
    };
  }

  function buildPayload(form) {
    var shippingAddress = state.checkout && state.checkout.shipping_address ? state.checkout.shipping_address : {};
    var billingAddress = state.checkout && state.checkout.billing_address ? state.checkout.billing_address : {};
    var nextShipping = state.cart && state.cart.needs_shipping ? collectAddress(form, 'shipping', shippingAddress) : collectAddress(form, 'billing', billingAddress);
    var sameAsShipping = !!(form.elements.billing_same_as_shipping && form.elements.billing_same_as_shipping.checked);
    var nextBilling = sameAsShipping ? Object.assign({}, nextShipping, {
      email: form.elements.billing_email ? form.elements.billing_email.value.trim() : '',
      phone: form.elements.billing_phone ? form.elements.billing_phone.value.trim() : '',
    }) : collectAddress(form, 'billing', billingAddress);
    var paymentField = form.querySelector('input[name="payment_method"]:checked');

    return {
      billing_address: nextBilling,
      shipping_address: nextShipping,
      payment_method: paymentField ? paymentField.value : '',
      customer_note: form.elements.customer_note ? form.elements.customer_note.value.trim() : '',
      create_account: false,
    };
  }

  function canSyncAddress(payload) {
    var shipping = payload.shipping_address || {};
    var billing = payload.billing_address || {};
    var address = state.cart && state.cart.needs_shipping ? shipping : billing;

    return !!(address.first_name && address.last_name && address.address_1 && address.city && address.postcode && address.country && billing.email);
  }

  function syncAddresses() {
    var form = getForm();

    if (!form || state.busy) {
      return;
    }

    var payload = buildPayload(form);

    if (!canSyncAddress(payload)) {
      return;
    }

    setBusy(true);
    state.checkout = Object.assign({}, state.checkout || {}, {
      billing_address: payload.billing_address,
      shipping_address: payload.shipping_address,
      payment_method: payload.payment_method,
      customer_note: payload.customer_note,
    });
    store.updateCustomer({
      billing_address: payload.billing_address,
      shipping_address: payload.shipping_address,
    }).then(function (cart) {
      state.cart = cart;
      renderSummary(cart);
      renderForm(cart, state.checkout || {});
      emitCartUpdated(cart);
    }).catch(function () {
      // Ignore address sync validation until final submit.
    }).finally(function () {
      setBusy(false);
    });
  }

  function refresh() {
    renderLoading();

    return Promise.all([store.getCart(), store.getCheckout()])
      .then(function (results) {
        state.cart = results[0];
        state.checkout = results[1];

        if (!state.checkout.payment_method) {
          var methods = store.getPaymentMethods();
          if (methods.length) {
            state.checkout.payment_method = methods[0].id;
          }
        }

        renderForm(state.cart, state.checkout);
        renderSummary(state.cart);
        emitCartUpdated(state.cart);
      })
      .catch(function (error) {
        setNotice((error && error.message) || i18n('checkoutError', 'We could not process checkout. Please review your details and try again.'), true);
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!formRoot()) {
      return;
    }

    refresh();

    document.addEventListener('change', function (event) {
      var form = getForm();

      if (!form) {
        return;
      }

      if (event.target.name === 'billing_same_as_shipping') {
        var hidden = event.target.checked;
        var billingFields = form.querySelector('[data-billing-address-fields]');
        if (billingFields) {
          billingFields.hidden = hidden;
        }
      }

      if (event.target.matches('input[type="radio"][data-package-id]')) {
        setBusy(true);
        store.selectShippingRate(event.target.getAttribute('data-package-id') || '', event.target.value)
          .then(function (cart) {
            state.cart = cart;
            renderForm(cart, state.checkout || {});
            renderSummary(cart);
            emitCartUpdated(cart);
          })
          .catch(function (error) {
            setNotice((error && error.message) || i18n('updateError', 'We could not update your cart. Please try again.'), true);
          })
          .finally(function () {
            setBusy(false);
          });
        return;
      }

      if (event.target.name === 'payment_method') {
        if (state.checkout) {
          state.checkout.payment_method = event.target.value;
        }
      }

      if (event.target.name === 'shipping_country' || event.target.name === 'billing_country') {
        state.checkout = Object.assign({}, state.checkout || {}, buildPayload(form));
        renderForm(state.cart, state.checkout);
      }

      window.clearTimeout(state.syncTimer);
      state.syncTimer = window.setTimeout(syncAddresses, 350);
    });

    document.addEventListener('click', function (event) {
      var removeCoupon = event.target.closest('[data-remove-coupon]');

      if (!removeCoupon || state.busy) {
        return;
      }

      event.preventDefault();
      setBusy(true);
      store.removeCoupon(removeCoupon.getAttribute('data-remove-coupon') || '')
        .then(function (cart) {
          state.cart = cart;
          renderSummary(cart);
          emitCartUpdated(cart);
        })
        .catch(function (error) {
          setNotice((error && error.message) || i18n('updateError', 'We could not update your cart. Please try again.'), true);
        })
        .finally(function () {
          setBusy(false);
        });
    });

    document.addEventListener('submit', function (event) {
      var couponForm = event.target.closest('[data-checkout-coupon-form]');

      if (couponForm) {
        event.preventDefault();

        if (state.busy) {
          return;
        }

        var codeField = couponForm.querySelector('[name="coupon_code"]');
        var code = codeField ? codeField.value.trim() : '';

        if (!code) {
          return;
        }

        setBusy(true);
        store.applyCoupon(code)
          .then(function (cart) {
            state.cart = cart;
            if (codeField) {
              codeField.value = '';
            }
            renderSummary(cart);
            emitCartUpdated(cart);
          })
          .catch(function (error) {
            setNotice((error && error.message) || i18n('updateError', 'We could not update your cart. Please try again.'), true);
          })
          .finally(function () {
            setBusy(false);
          });

        return;
      }

      var form = event.target.closest('[data-custom-checkout-form]');

      if (!form || state.busy) {
        return;
      }

      event.preventDefault();
      setNotice('', false);

      var payload = buildPayload(form);
      var selectedMethod = (store.getPaymentMethods() || []).find(function (method) {
        return method.id === payload.payment_method;
      });

      if (selectedMethod && selectedMethod.has_fields) {
        setNotice(i18n('paymentUnsupported', 'This gateway needs extra fields that are not exposed in the custom checkout yet.'), true);
        return;
      }

      setBusy(true);

      store.checkout(payload)
        .then(function (response) {
          if (response && response.payment_result && response.payment_result.redirect_url) {
            setNotice(i18n('orderPlaced', 'Order created. Redirecting…'), false);
            window.location.href = response.payment_result.redirect_url;
            return;
          }

          if (response && response.order_id && response.order_key) {
            window.location.href = (config.orderReceivedBase || ((config.checkoutUrl || '/checkout/') + 'order-received/')) + response.order_id + '/?key=' + encodeURIComponent(response.order_key);
            return;
          }

          window.location.href = config.checkoutUrl || window.location.href;
        })
        .catch(function (error) {
          setNotice((error && error.message) || i18n('checkoutError', 'We could not process checkout. Please review your details and try again.'), true);

          if (error && error.response && error.response.data && error.response.data.cart) {
            state.cart = error.response.data.cart;
            renderSummary(state.cart);
          }
        })
        .finally(function () {
          setBusy(false);
        });
    });
  });
})();
