(function () {
  'use strict';

  var store = window.StarterkitCommerceStore;
  var config = window.starterkitCommerce || {};
  var drawerConfig = window.starterkitCartDrawer || {};

  if (!store) {
    return;
  }

  var state = {
    drawer: null,
    busy: false,
    loading: false,
    cart: null,
    addInFlight: false,
    toastTimer: null,
  };

  function i18n(key, fallback) {
    return (config.i18n && config.i18n[key]) || fallback || '';
  }

  function syncRefs() {
    state.drawer = document.getElementById('starterkit-cart-drawer');
  }

  function itemCountLabel(count) {
    var template = count === 1 ? i18n('itemCountSingular', '%d item') : i18n('itemCountPlural', '%d items');
    return template.replace('%d', String(count));
  }

  function escape(value) {
    return store.escapeHtml(value);
  }

  function findRoot() {
    syncRefs();
    return state.drawer ? state.drawer.querySelector('[data-cart-drawer-root]') : null;
  }

  function setToast(message, isError) {
    syncRefs();

    if (!state.drawer) {
      return;
    }

    var toast = state.drawer.querySelector('.starterkit-cart-drawer__toast');

    if (!toast) {
      return;
    }

    if (state.toastTimer) {
      window.clearTimeout(state.toastTimer);
      state.toastTimer = null;
    }

    toast.textContent = message || '';
    toast.classList.toggle('is-visible', !!message);
    toast.classList.toggle('is-error', !!message && !!isError);

    if (message) {
      state.toastTimer = window.setTimeout(function () {
        toast.classList.remove('is-visible', 'is-error');
        toast.textContent = '';
        state.toastTimer = null;
      }, 3200);
    }
  }

  function setBusy(busy) {
    state.busy = busy;
    syncRefs();

    if (state.drawer) {
      state.drawer.classList.toggle('is-busy', busy);
    }
  }

  function openDrawer() {
    syncRefs();

    if (!state.drawer) {
      return;
    }

    state.drawer.classList.add('is-open');
    state.drawer.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('has-cart-drawer');
    document.body.classList.add('has-cart-drawer');

    if (!state.cart && !state.loading) {
      refreshCart();
    }
  }

  function closeDrawer() {
    syncRefs();

    if (!state.drawer) {
      return;
    }

    state.drawer.classList.remove('is-open');
    state.drawer.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('has-cart-drawer');
    document.body.classList.remove('has-cart-drawer');
  }

  function renderProgress(cart) {
    var threshold = Number(drawerConfig.freeShippingThreshold || config.freeShippingThreshold || 0);

    if (!threshold) {
      return '';
    }

    var totals = cart && cart.totals ? cart.totals : {};
    var subtotal = Number(totals.total_items || 0) / Math.pow(10, Number(totals.currency_minor_unit || 2));
    var remaining = Math.max(0, threshold - subtotal);
    var percent = Math.min(100, Math.round((subtotal / threshold) * 100));
    var copy = remaining > 0
      ? i18n('freeShippingRemaining', 'Add %s more for free shipping').replace('%s', store.formatPrice(Math.round(remaining * Math.pow(10, Number(totals.currency_minor_unit || 2))), totals))
      : i18n('freeShippingUnlocked', 'You unlocked free shipping.');

    return '' +
      '<div class="starterkit-cart-drawer__progress">' +
        '<p>' + escape(copy) + '</p>' +
        '<div class="starterkit-cart-drawer__progress-bar" aria-hidden="true"><span style="width:' + percent + '%"></span></div>' +
      '</div>';
  }

  function renderUpsells() {
    var upsells = Array.isArray(drawerConfig.upsells) ? drawerConfig.upsells : [];

    if (!upsells.length) {
      return '';
    }

    return '' +
      '<div class="starterkit-cart-drawer__upsell">' +
        '<div class="starterkit-cart-drawer__upsell-header"><h3>You may also like</h3></div>' +
        '<div class="starterkit-cart-drawer__upsell-grid">' +
          upsells.map(function (product) {
            var action = product.can_add
              ? '<button type="button" class="button button-secondary starterkit-cart-drawer__upsell-add" data-product-id="' + escape(product.id) + '">' + escape(product.add_to_text || 'Add to cart') + '</button>'
              : '<a href="' + escape(product.permalink) + '" class="button button-secondary">View product</a>';

            return '' +
              '<div class="starterkit-cart-drawer__upsell-card">' +
                '<a class="starterkit-cart-drawer__upsell-image" href="' + escape(product.permalink) + '">' + (product.image_html || '') + '</a>' +
                '<div class="starterkit-cart-drawer__upsell-copy">' +
                  '<a class="starterkit-cart-drawer__upsell-title" href="' + escape(product.permalink) + '">' + escape(product.name) + '</a>' +
                  '<div class="starterkit-cart-drawer__upsell-price">' + (product.price_html || '') + '</div>' +
                  action +
                '</div>' +
              '</div>';
          }).join('') +
        '</div>' +
      '</div>';
  }

  function renderItems(cart) {
    var items = Array.isArray(cart.items) ? cart.items : [];

    if (!items.length) {
      return '' +
        '<div class="starterkit-cart-drawer__empty">' +
          '<p>' + escape(i18n('emptyCart', 'Your cart is empty.')) + '</p>' +
          '<p>' + escape(i18n('emptyDrawerBody', 'Add something good and your bag will appear here.')) + '</p>' +
          '<a class="button button-primary" href="' + escape(config.shopUrl || '/') + '">' + escape(i18n('continueShopping', 'Continue shopping')) + '</a>' +
        '</div>' +
        renderUpsells();
    }

    return '' +
      '<ul class="starterkit-cart-drawer__items">' +
        items.map(function (item) {
          var image = store.itemImage(item);
          var imageHtml = image ? '<img src="' + escape(image) + '" alt="' + escape(item.name) + '">' : '';
          var meta = store.itemMeta(item);
          var permalink = item.permalink || '#';

          return '' +
            '<li class="starterkit-cart-drawer__item" data-cart-item-key="' + escape(item.key) + '" data-quantity="' + escape(item.quantity) + '">' +
              '<div class="starterkit-cart-drawer__item-image"><a href="' + escape(permalink) + '">' + imageHtml + '</a></div>' +
              '<div class="starterkit-cart-drawer__item-content">' +
                '<h3 class="starterkit-cart-drawer__item-title"><a href="' + escape(permalink) + '">' + escape(item.name) + '</a></h3>' +
                (meta ? '<div class="starterkit-cart-drawer__item-variation">' + meta + '</div>' : '') +
                '<div class="starterkit-cart-drawer__item-bottom">' +
                  '<div class="starterkit-cart-drawer__quantity" aria-label="Quantity controls">' +
                    '<button type="button" class="starterkit-cart-drawer__qty-button" data-quantity-delta="-1" data-cart-item-key="' + escape(item.key) + '">-</button>' +
                    '<span class="starterkit-cart-drawer__qty-value">' + escape(item.quantity) + '</span>' +
                    '<button type="button" class="starterkit-cart-drawer__qty-button" data-quantity-delta="1" data-cart-item-key="' + escape(item.key) + '">+</button>' +
                  '</div>' +
                  '<div class="starterkit-cart-drawer__item-price">' + escape(store.formatPrice(item.prices.price, item.prices)) + '</div>' +
                '</div>' +
                '<button type="button" class="starterkit-cart-drawer__remove" data-cart-item-key="' + escape(item.key) + '" aria-label="' + escape(i18n('remove', 'Remove')) + '">&times;</button>' +
              '</div>' +
            '</li>';
        }).join('') +
      '</ul>' +
      renderUpsells();
  }

  function updateCountBadges(cart) {
    var count = cart && cart.items_count ? cart.items_count : 0;

    document.querySelectorAll('.header-cart-count').forEach(function (node) {
      node.textContent = String(count);
    });
  }

  function render(cart) {
    var root = findRoot();

    if (!root || !cart) {
      return;
    }

    root.innerHTML = '' +
      '<div class="starterkit-cart-drawer__header">' +
        '<div>' +
          '<h2>' + escape(i18n('cartDrawerTitle', 'Your cart')) + '</h2>' +
          '<p class="starterkit-cart-drawer__meta">' + escape(itemCountLabel(cart.items_count || 0)) + '</p>' +
        '</div>' +
        '<button type="button" class="starterkit-cart-drawer__close" data-cart-drawer-close aria-label="Close cart drawer"><span aria-hidden="true">&times;</span></button>' +
      '</div>' +
      '<div class="starterkit-cart-drawer__body">' +
        '<div class="starterkit-cart-drawer__status" aria-live="polite">' + escape(i18n('updating', 'Updating your cart...')) + '</div>' +
        renderProgress(cart) +
        renderItems(cart) +
      '</div>' +
      '<div class="starterkit-cart-drawer__footer">' +
        '<div class="starterkit-cart-drawer__subtotal"><span>' + escape(i18n('subtotal', 'Subtotal')) + '</span><strong>' + escape(store.cartMoney(cart, 'total_items')) + '</strong></div>' +
        '<div class="starterkit-cart-drawer__actions">' +
          '<a class="button button-secondary" href="' + escape(drawerConfig.cartUrl || config.cartUrl || '#') + '">' + escape(i18n('viewCart', 'View cart')) + '</a>' +
          '<a class="button button-primary" href="' + escape(drawerConfig.checkoutUrl || config.checkoutUrl || '#') + '">' + escape(i18n('checkout', 'Checkout')) + '</a>' +
        '</div>' +
      '</div>';

    updateCountBadges(cart);
  }

  function emitCartUpdated(cart) {
    document.dispatchEvent(new CustomEvent('starterkit:cart-updated', {
      detail: {
        cart: cart,
      },
    }));
  }

  function renderLoading() {
    var root = findRoot();

    if (!root) {
      return;
    }

    root.innerHTML = '<div class="starterkit-cart-drawer__body"><div class="starterkit-cart-drawer__progress"><p>' + escape(i18n('loadingCart', 'Loading your cart...')) + '</p></div></div>';
  }

  function refreshCart() {
    if (state.loading) {
      return Promise.resolve(state.cart);
    }

    state.loading = true;
    renderLoading();

    return store.getCart()
      .then(function (cart) {
        state.cart = cart;
        render(cart);
        emitCartUpdated(cart);
        return cart;
      })
      .catch(function (error) {
        setToast((error && error.message) || i18n('updateError', 'We could not update your cart. Please try again.'), true);
        return null;
      })
      .finally(function () {
        state.loading = false;
      });
  }

  function updateItem(key, quantity) {
    if (state.busy) {
      return;
    }

    setBusy(true);

    var action = quantity <= 0 ? store.removeItem(key) : store.updateItem(key, quantity);

    action
      .then(function (cart) {
        state.cart = cart;
        render(cart);
        emitCartUpdated(cart);
      })
      .catch(function (error) {
        setToast((error && error.message) || i18n('updateError', 'We could not update your cart. Please try again.'), true);
      })
      .finally(function () {
        setBusy(false);
      });
  }

  function setButtonBusy(button, busy) {
    if (!button) {
      return;
    }

    button.classList.toggle('is-loading', busy);

    if ('disabled' in button) {
      button.disabled = busy;
    }
  }

  function addSimpleProduct(productId, button) {
    if (!productId || state.addInFlight) {
      return;
    }

    state.addInFlight = true;
    setButtonBusy(button, true);

    store.addItem({
      id: Number(productId),
      quantity: 1,
    }).then(function (cart) {
      state.cart = cart;
      render(cart);
      emitCartUpdated(cart);
      openDrawer();
      setToast(itemCountLabel(cart.items_count || 0), false);
    }).catch(function (error) {
      setToast((error && error.message) || i18n('updateError', 'We could not update your cart. Please try again.'), true);
    }).finally(function () {
      state.addInFlight = false;
      setButtonBusy(button, false);
    });
  }

  function buildVariationPayload(form) {
    var variation = [];

    form.querySelectorAll('select[name^="attribute_"], input[name^="attribute_"]:checked').forEach(function (field) {
      if (!field.name || !field.value) {
        return;
      }

      variation.push({
        attribute: field.name,
        value: field.value,
      });
    });

    return variation;
  }

  function handleProductFormSubmit(form, submitter) {
    if (!form || state.addInFlight) {
      return;
    }

    if (form.classList.contains('grouped_form') || form.classList.contains('external')) {
      return;
    }

    var quantityField = form.querySelector('input.qty');
    var addToCartField = form.querySelector('[name="add-to-cart"]');
    var variationIdField = form.querySelector('[name="variation_id"]');
    var wootifyVariantField = form.querySelector('[name="wootify_variant_id"]');
    var productId = variationIdField && variationIdField.value ? variationIdField.value : (addToCartField && addToCartField.value ? addToCartField.value : '');

    if (!productId || (wootifyVariantField && wootifyVariantField.value)) {
      return;
    }

    state.addInFlight = true;
    setButtonBusy(submitter, true);

    store.addItem({
      id: Number(productId),
      quantity: quantityField && quantityField.value ? Number(quantityField.value) : 1,
      variation: buildVariationPayload(form),
    }).then(function (cart) {
      state.cart = cart;
      render(cart);
      emitCartUpdated(cart);
      openDrawer();
      setToast(escape(i18n('cartTitle', 'Your cart')), false);
    }).catch(function (error) {
      setToast((error && error.message) || i18n('updateError', 'We could not update your cart. Please try again.'), true);
    }).finally(function () {
      state.addInFlight = false;
      setButtonBusy(submitter, false);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    syncRefs();
    refreshCart();

    document.addEventListener('starterkit:cart-updated', function (event) {
      if (event.detail && event.detail.cart) {
        state.cart = event.detail.cart;
        render(event.detail.cart);
      }
    });

    document.addEventListener('click', function (event) {
      var openTrigger = event.target.closest('[data-cart-drawer-open], .header-cart-link, .header-cart-button');
      var closeTrigger = event.target.closest('[data-cart-drawer-close]');
      var qtyButton = event.target.closest('.starterkit-cart-drawer__qty-button');
      var removeButton = event.target.closest('.starterkit-cart-drawer__remove');
      var upsellButton = event.target.closest('.starterkit-cart-drawer__upsell-add');
      var ajaxAddButton = event.target.closest('.ajax_add_to_cart');

      if (openTrigger) {
        event.preventDefault();
        openDrawer();
        return;
      }

      if (closeTrigger) {
        event.preventDefault();
        closeDrawer();
        return;
      }

      if (qtyButton) {
        event.preventDefault();

        var item = qtyButton.closest('.starterkit-cart-drawer__item');
        var currentQuantity = item ? parseInt(item.getAttribute('data-quantity') || '1', 10) : 1;
        var delta = parseInt(qtyButton.getAttribute('data-quantity-delta') || '0', 10);
        var itemKey = qtyButton.getAttribute('data-cart-item-key') || '';

        if (itemKey) {
          updateItem(itemKey, Math.max(0, currentQuantity + delta));
        }

        return;
      }

      if (removeButton) {
        event.preventDefault();
        updateItem(removeButton.getAttribute('data-cart-item-key') || '', 0);
        return;
      }

      if (upsellButton) {
        event.preventDefault();
        addSimpleProduct(upsellButton.getAttribute('data-product-id') || '', upsellButton);
        return;
      }

      if (ajaxAddButton && !ajaxAddButton.closest('#starterkit-cart-drawer')) {
        event.preventDefault();
        addSimpleProduct(ajaxAddButton.getAttribute('data-product_id') || '', ajaxAddButton);
      }
    });

    document.addEventListener('submit', function (event) {
      var form = event.target;

      if (!form.matches('.single-product form.cart')) {
        return;
      }

      event.preventDefault();
      handleProductFormSubmit(form, event.submitter || form.querySelector('.single_add_to_cart_button'));
    }, true);

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeDrawer();
      }
    });
  });
})();
