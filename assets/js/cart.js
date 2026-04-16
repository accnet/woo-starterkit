(function () {
  'use strict';

  var store = window.StarterkitCommerceStore;
  var config = window.starterkitCommerce || {};

  if (!store) {
    return;
  }

  var state = {
    cart: null,
    busy: false,
  };

  function i18n(key, fallback) {
    return (config.i18n && config.i18n[key]) || fallback || '';
  }

  function root() {
    return document.querySelector('[data-commerce-cart-root]');
  }

  function summaryRoot() {
    return document.querySelector('[data-commerce-cart-summary]');
  }

  function noticeRoot() {
    return document.querySelector('[data-commerce-notice]');
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
    node.className = 'starterkit-commerce__notice' + (message ? ' is-visible' : '') + (isError ? ' is-error' : '');
  }

  function setBusy(busy) {
    state.busy = busy;
    document.documentElement.classList.toggle('starterkit-commerce-busy', busy);
  }

  function emitCartUpdated(cart) {
    document.dispatchEvent(new CustomEvent('starterkit:cart-updated', {
      detail: {
        cart: cart,
      },
    }));
  }

  function renderLoading() {
    if (root()) {
      root().innerHTML = '<div class="starterkit-commerce__loading">' + escape(i18n('loadingCart', 'Loading your cart...')) + '</div>';
    }

    if (summaryRoot()) {
      summaryRoot().innerHTML = '<div class="starterkit-commerce__loading">' + escape(i18n('loadingCart', 'Loading your cart...')) + '</div>';
    }
  }

  function renderCoupons(cart) {
    var coupons = Array.isArray(cart.coupons) ? cart.coupons : [];

    return '' +
      '<section class="starterkit-commerce-card starterkit-commerce-card--soft">' +
        '<div class="starterkit-commerce-card__head starterkit-commerce-card__head--inline">' +
          '<h2>' + escape(i18n('discountCode', 'Discount code')) + '</h2>' +
          '<span class="starterkit-commerce-card__caption">Gift card or promo</span>' +
        '</div>' +
        '<form class="starterkit-commerce-coupon" data-cart-coupon-form>' +
          '<input type="text" name="coupon_code" placeholder="' + escape(i18n('discountCode', 'Discount code')) + '" autocomplete="off">' +
          '<button type="submit" class="button button-secondary">' + escape(i18n('apply', 'Apply')) + '</button>' +
        '</form>' +
        (coupons.length ? '<div class="starterkit-commerce-tags">' + coupons.map(function (coupon) {
          return '<button type="button" class="starterkit-commerce-tag" data-remove-coupon="' + escape(coupon.code) + '">' + escape(coupon.code) + ' <span aria-hidden="true">&times;</span></button>';
        }).join('') + '</div>' : '') +
      '</section>';
  }

  function renderShipping(cart) {
    var packages = Array.isArray(cart.shipping_rates) ? cart.shipping_rates : [];

    if (!packages.length) {
      return '' +
        '<section class="starterkit-commerce-card starterkit-commerce-card--soft">' +
          '<div class="starterkit-commerce-card__head"><h2>' + escape(i18n('shippingMethod', 'Shipping method')) + '</h2></div>' +
          '<p class="starterkit-commerce__muted">' + escape(i18n('shippingPending', 'Enter shipping address')) + '</p>' +
        '</section>';
    }

    return '' +
      '<section class="starterkit-commerce-card">' +
        '<div class="starterkit-commerce-card__head"><h2>' + escape(i18n('shippingMethod', 'Shipping method')) + '</h2></div>' +
        '<div class="starterkit-commerce-rates">' +
          packages.map(function (pkg, packageIndex) {
            var rates = Array.isArray(pkg.shipping_rates) ? pkg.shipping_rates : [];

            return '' +
              '<div class="starterkit-commerce-rates__group">' +
                rates.map(function (rate) {
                  var id = 'shipping-rate-' + packageIndex + '-' + escape(rate.rate_id);
                  return '' +
                    '<label class="starterkit-commerce-rate" for="' + id + '">' +
                      '<input id="' + id + '" type="radio" name="shipping_rate_' + packageIndex + '" value="' + escape(rate.rate_id) + '" data-package-id="' + escape(pkg.package_id) + '" ' + (rate.selected ? 'checked' : '') + '>' +
                      '<span class="starterkit-commerce-rate__copy">' +
                        '<strong>' + escape(rate.name) + '</strong>' +
                        '<small>' + escape(rate.description || '') + '</small>' +
                      '</span>' +
                      '<span class="starterkit-commerce-rate__price">' + escape(store.formatPrice(rate.price, cart.totals)) + '</span>' +
                    '</label>';
                }).join('') +
              '</div>';
          }).join('') +
        '</div>' +
      '</section>';
  }

  function renderCartItems(cart) {
    var items = Array.isArray(cart.items) ? cart.items : [];

    if (!items.length) {
      return '' +
        '<section class="starterkit-commerce-card starterkit-commerce-card--empty">' +
          '<h2>' + escape(i18n('emptyCart', 'Your cart is empty.')) + '</h2>' +
          '<p>' + escape(i18n('emptyCartBody', 'Add products before continuing to checkout.')) + '</p>' +
          '<a class="button button-primary" href="' + escape(config.shopUrl || '/') + '">' + escape(i18n('backToShop', 'Continue shopping')) + '</a>' +
        '</section>';
    }

    return '' +
      '<section class="starterkit-commerce-card">' +
        '<div class="starterkit-commerce-card__head starterkit-commerce-card__head--inline">' +
          '<h2>' + escape(i18n('cartTitle', 'Your cart')) + '</h2>' +
          '<span class="starterkit-commerce-card__caption">' + escape((cart.items_count || 0) + '') + '</span>' +
        '</div>' +
        '<div class="starterkit-commerce-items">' +
          items.map(function (item) {
            var image = store.itemImage(item);
            var meta = store.itemMeta(item);
            return '' +
              '<article class="starterkit-commerce-item" data-cart-item-key="' + escape(item.key) + '" data-quantity="' + escape(item.quantity) + '">' +
                '<a class="starterkit-commerce-item__image" href="' + escape(item.permalink || '#') + '">' + (image ? '<img src="' + escape(image) + '" alt="' + escape(item.name) + '">' : '') + '</a>' +
                '<div class="starterkit-commerce-item__content">' +
                  '<div class="starterkit-commerce-item__top">' +
                    '<div>' +
                      '<h3><a href="' + escape(item.permalink || '#') + '">' + escape(item.name) + '</a></h3>' +
                      (meta ? '<div class="starterkit-commerce-item__meta">' + meta + '</div>' : '') +
                    '</div>' +
                    '<button type="button" class="starterkit-commerce-item__remove" data-remove-item="' + escape(item.key) + '">' + escape(i18n('remove', 'Remove')) + '</button>' +
                  '</div>' +
                  '<div class="starterkit-commerce-item__bottom">' +
                    '<div class="starterkit-commerce-qty">' +
                      '<button type="button" data-qty-delta="-1" data-cart-item-key="' + escape(item.key) + '">-</button>' +
                      '<span>' + escape(item.quantity) + '</span>' +
                      '<button type="button" data-qty-delta="1" data-cart-item-key="' + escape(item.key) + '">+</button>' +
                    '</div>' +
                    '<div class="starterkit-commerce-item__pricing">' +
                      '<strong>' + escape(store.formatPrice(item.totals.line_total, cart.totals)) + '</strong>' +
                      '<small>' + escape(store.formatPrice(item.prices.price, item.prices)) + ' each</small>' +
                    '</div>' +
                  '</div>' +
                '</div>' +
              '</article>';
          }).join('') +
        '</div>' +
      '</section>' +
      renderCoupons(cart) +
      renderShipping(cart);
  }

  function renderSummary(cart) {
    var totals = cart.totals || {};
    var couponLines = Array.isArray(cart.coupons) ? cart.coupons : [];

    return '' +
      '<section class="starterkit-commerce-card starterkit-commerce-card--summary">' +
        '<div class="starterkit-commerce-card__head starterkit-commerce-card__head--inline"><h2>' + escape(i18n('orderSummary', 'Order summary')) + '</h2><span class="starterkit-commerce-card__caption">Estimated</span></div>' +
        '<div class="starterkit-commerce-summary__rows">' +
          '<div><span>' + escape(i18n('subtotal', 'Subtotal')) + '</span><strong>' + escape(store.cartMoney(cart, 'total_items')) + '</strong></div>' +
          (couponLines.map(function (coupon) {
            return '<div><span>' + escape(i18n('discount', 'Discount')) + ' · ' + escape(coupon.code) + '</span><strong>-' + escape(store.formatPrice(coupon.totals.total_discount, totals)) + '</strong></div>';
          }).join('')) +
          '<div><span>' + escape(i18n('shipping', 'Shipping')) + '</span><strong>' + escape(store.cartMoney(cart, 'total_shipping')) + '</strong></div>' +
          '<div class="is-total"><span>' + escape(i18n('total', 'Total')) + '</span><strong>' + escape(store.cartMoney(cart, 'total_price')) + '</strong></div>' +
        '</div>' +
        '<p class="starterkit-commerce-summary__meta">Taxes and shipping are calculated at checkout.</p>' +
        '<a class="button button-primary starterkit-commerce-summary__cta" href="' + escape(config.checkoutUrl || '#') + '">' + escape(i18n('checkout', 'Checkout')) + '</a>' +
      '</section>';
  }

  function render(cart) {
    if (root()) {
      root().innerHTML = renderCartItems(cart);
    }

    if (summaryRoot()) {
      summaryRoot().innerHTML = renderSummary(cart);
    }
  }

  function refresh() {
    renderLoading();

    return store.getCart()
      .then(function (cart) {
        state.cart = cart;
        render(cart);
        emitCartUpdated(cart);
      })
      .catch(function (error) {
        setNotice((error && error.message) || i18n('updateError', 'We could not update your cart. Please try again.'), true);
      });
  }

  function updateQuantity(key, quantity) {
    if (!key || state.busy) {
      return;
    }

    setBusy(true);
    setNotice('', false);

    var request = quantity <= 0 ? store.removeItem(key) : store.updateItem(key, quantity);

    request.then(function (cart) {
      state.cart = cart;
      render(cart);
      emitCartUpdated(cart);
    }).catch(function (error) {
      setNotice((error && error.message) || i18n('updateError', 'We could not update your cart. Please try again.'), true);
    }).finally(function () {
      setBusy(false);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!root()) {
      return;
    }

    refresh();

    document.addEventListener('click', function (event) {
      var qtyButton = event.target.closest('[data-qty-delta]');
      var removeButton = event.target.closest('[data-remove-item]');
      var removeCoupon = event.target.closest('[data-remove-coupon]');

      if (qtyButton) {
        event.preventDefault();

        var item = qtyButton.closest('[data-cart-item-key]');
        var currentQuantity = item ? parseInt(item.getAttribute('data-quantity') || '1', 10) : 1;
        var delta = parseInt(qtyButton.getAttribute('data-qty-delta') || '0', 10);
        updateQuantity(qtyButton.getAttribute('data-cart-item-key') || '', Math.max(0, currentQuantity + delta));
        return;
      }

      if (removeButton) {
        event.preventDefault();
        updateQuantity(removeButton.getAttribute('data-remove-item') || '', 0);
        return;
      }

      if (removeCoupon) {
        event.preventDefault();

        if (state.busy) {
          return;
        }

        setBusy(true);
        store.removeCoupon(removeCoupon.getAttribute('data-remove-coupon') || '')
          .then(function (cart) {
            state.cart = cart;
            render(cart);
            emitCartUpdated(cart);
          })
          .catch(function (error) {
            setNotice((error && error.message) || i18n('updateError', 'We could not update your cart. Please try again.'), true);
          })
          .finally(function () {
            setBusy(false);
          });
      }
    });

    document.addEventListener('change', function (event) {
      var shippingRate = event.target.closest('input[type="radio"][data-package-id]');

      if (!shippingRate || state.busy) {
        return;
      }

      setBusy(true);
      store.selectShippingRate(shippingRate.getAttribute('data-package-id') || '', shippingRate.value)
        .then(function (cart) {
          state.cart = cart;
          render(cart);
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
      var form = event.target.closest('[data-cart-coupon-form]');

      if (!form || state.busy) {
        return;
      }

      event.preventDefault();

      var codeField = form.querySelector('[name="coupon_code"]');
      var code = codeField ? codeField.value.trim() : '';

      if (!code) {
        return;
      }

      setBusy(true);
      store.applyCoupon(code)
        .then(function (cart) {
          if (codeField) {
            codeField.value = '';
          }

          state.cart = cart;
          render(cart);
          emitCartUpdated(cart);
        })
        .catch(function (error) {
          setNotice((error && error.message) || i18n('updateError', 'We could not update your cart. Please try again.'), true);
        })
        .finally(function () {
          setBusy(false);
        });
    });
  });
})();
