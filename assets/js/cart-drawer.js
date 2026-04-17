(function () {
  var state = {
    drawer: null,
    panel: null,
    busy: false,
    activeAddButton: null,
    toastTimer: null,
    productSubmitInFlight: false,
    previousCartItemKeys: [],
    pendingHighlightKey: '',
    shouldHighlightOnOpen: false,
  };

  function applyFragments(fragments) {
    if (!fragments) {
      return;
    }

    Object.keys(fragments).forEach(function (selector) {
      document.querySelectorAll(selector).forEach(function (node) {
        node.outerHTML = fragments[selector];
      });
    });
  }

  function syncRefs() {
    state.drawer = document.getElementById('starterkit-cart-drawer');
    state.panel = state.drawer ? state.drawer.querySelector('.starterkit-cart-drawer__panel') : null;
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

    if (state.shouldHighlightOnOpen) {
      highlightPendingItem();
      state.shouldHighlightOnOpen = false;
    }
  }

  function setStatus(message, isError) {
    syncRefs();

    if (!state.drawer) {
      return;
    }

    state.drawer.querySelectorAll('.starterkit-cart-drawer__status').forEach(function (node) {
      node.textContent = message || '';
      node.classList.toggle('is-visible', !!message);
      node.classList.toggle('is-error', !!message && !!isError);
    });
  }

  function showToast(message, isError) {
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

  function setBusy(busy, item) {
    state.busy = busy;
    syncRefs();

    if (state.drawer) {
      state.drawer.classList.toggle('is-busy', busy);
    }

    if (item) {
      item.classList.toggle('is-loading', busy);
      item.querySelectorAll('.starterkit-cart-drawer__qty-button, .starterkit-cart-drawer__remove').forEach(function (button) {
        button.disabled = busy;
      });
    }

    if (busy) {
      setStatus(starterkitCartDrawer.i18n && starterkitCartDrawer.i18n.updating ? starterkitCartDrawer.i18n.updating : '', false);
    } else {
      setStatus('', false);
    }
  }

  function setAddButtonState(button, busy) {
    if (!button) {
      return;
    }

    button.classList.toggle('is-loading', busy);
    button.setAttribute('aria-busy', busy ? 'true' : 'false');

    if ('disabled' in button) {
      button.disabled = busy;
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

  function getCartItemKeys(root) {
    return Array.prototype.map.call(
      (root || document).querySelectorAll('.starterkit-cart-drawer__item[data-cart-item-key]'),
      function (item) {
        return item.getAttribute('data-cart-item-key') || '';
      }
    ).filter(Boolean);
  }

  function prepareAddHighlight() {
    state.previousCartItemKeys = getCartItemKeys(document);
    state.pendingHighlightKey = '';
    state.shouldHighlightOnOpen = true;
  }

  function resolvePendingHighlightKey() {
    if (!state.shouldHighlightOnOpen) {
      return;
    }

    var currentKeys = getCartItemKeys(state.drawer || document);
    var newKeys = currentKeys.filter(function (key) {
      return state.previousCartItemKeys.indexOf(key) === -1;
    });

    if (newKeys.length) {
      state.pendingHighlightKey = newKeys[newKeys.length - 1];
    } else if (currentKeys.length) {
      state.pendingHighlightKey = currentKeys[currentKeys.length - 1];
    } else {
      state.pendingHighlightKey = '';
    }

    state.previousCartItemKeys = [];
  }

  function highlightPendingItem() {
    if (!state.drawer) {
      return;
    }

    state.drawer.querySelectorAll('.starterkit-cart-drawer__item.is-highlighted').forEach(function (item) {
      item.classList.remove('is-highlighted');
    });

    var selector = state.pendingHighlightKey
      ? '.starterkit-cart-drawer__item[data-cart-item-key="' + state.pendingHighlightKey + '"]'
      : '.starterkit-cart-drawer__item:last-child';
    var targetItem = state.drawer.querySelector(selector);

    if (targetItem) {
      targetItem.classList.add('is-highlighted');
      window.setTimeout(function () {
        targetItem.classList.remove('is-highlighted');
      }, 1400);
    }

    state.pendingHighlightKey = '';
  }

  function request(endpoint, payload) {
    var formData = new FormData();

    Object.keys(payload).forEach(function (key) {
      formData.append(key, payload[key]);
    });

    return fetch(starterkitCartDrawer.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    }).then(function (response) {
      return response.text().then(function (text) {
        return text ? JSON.parse(text) : null;
      });
    });
  }

  function requestWooEndpoint(endpoint, formData) {
    if (!starterkitCartDrawer.wcAjaxUrl) {
      return Promise.resolve(null);
    }

    return fetch(starterkitCartDrawer.wcAjaxUrl.replace('%%endpoint%%', endpoint), {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    }).then(function (response) {
      return response.text().then(function (text) {
        return text ? JSON.parse(text) : null;
      });
    });
  }

  function addProductToCart(button) {
    if (!button || state.busy) {
      return Promise.resolve(null);
    }

    var productId = button.getAttribute('data-product_id') || '';
    var quantity = button.getAttribute('data-quantity') || '1';

    if (!productId) {
      return Promise.resolve(null);
    }

    var formData = new FormData();
    formData.set('product_id', productId);
    formData.set('quantity', quantity);
    prepareAddHighlight();

    state.activeAddButton = button;
    setAddButtonState(button, true);

    return requestWooEndpoint('add_to_cart', formData)
      .then(function (response) {
        setAddButtonState(button, false);
        state.activeAddButton = null;

        if (response && response.error && response.product_url) {
          window.location.href = response.product_url;
          return response;
        }

        if (response && response.fragments) {
          refreshFromResponse(response, true);
          return response;
        }

        return refreshDrawerFragments(true);
      })
      .catch(function () {
        setAddButtonState(button, false);
        state.activeAddButton = null;
        var errorMessage = starterkitCartDrawer.i18n && starterkitCartDrawer.i18n.error ? starterkitCartDrawer.i18n.error : '';
        setStatus(errorMessage, true);
        showToast(errorMessage, true);
        return null;
      });
  }

  function refreshFromResponse(response, shouldOpen) {
    if (!response || !response.fragments) {
      return false;
    }

    applyFragments(response.fragments);
    syncRefs();
    resolvePendingHighlightKey();

    if (shouldOpen) {
      openDrawer();
    }

    return true;
  }

  function refreshDrawerFragments(shouldOpen) {
    return requestWooEndpoint('get_refreshed_fragments', new FormData()).then(function (response) {
      refreshFromResponse(response, shouldOpen);
      return response;
    });
  }

  function bindWooEvents() {
    if (!window.jQuery) {
      return;
    }

    window.jQuery(document.body).on('adding_to_cart', function (_event, button) {
      prepareAddHighlight();

      if (button && button.length) {
        state.activeAddButton = button[0];
        setAddButtonState(state.activeAddButton, true);
      }
    });

    window.jQuery(document.body).on('added_to_cart', function () {
      if (state.activeAddButton) {
        setAddButtonState(state.activeAddButton, false);
        state.activeAddButton = null;
      }

      syncRefs();
      openDrawer();
    });

    window.jQuery(document.body).on('ajax_request_not_sent', function (_event, button) {
      if (button && button.length) {
        setAddButtonState(button[0], false);
      }
    });
  }

  function shouldHandleSingleProductForm(form) {
    if (!form || !form.matches('.single-product form.cart') || !starterkitCartDrawer.wcAjaxUrl) {
      return false;
    }

    if (form.classList.contains('grouped_form') || form.classList.contains('external')) {
      return false;
    }

    return true;
  }

  function performSingleProductAddToCart(form, submitter) {
    if (state.productSubmitInFlight) {
      return Promise.resolve(null);
    }

    state.productSubmitInFlight = true;
    prepareAddHighlight();
    state.activeAddButton = submitter || form.querySelector('.single_add_to_cart_button');
    setAddButtonState(state.activeAddButton, true);

    var addToCartField = form.querySelector('[name="add-to-cart"]');
    var quantityField = form.querySelector('input.qty');
    var wcVariationField = form.querySelector('[name="variation_id"]');
    var wootifyVariantField = form.querySelector('[name="wootify_variant_id"]');
    var productId = '';

    if (addToCartField && addToCartField.value) {
      productId = addToCartField.value;
    } else if (submitter && submitter.value) {
      productId = submitter.value;
    }

    var formData = new FormData();
    formData.set('quantity', (quantityField && quantityField.value) ? quantityField.value : '1');

    if (wcVariationField && wcVariationField.value) {
      // Standard WC variation: send variation ID as product_id so WC resolves it natively
      formData.set('product_id', wcVariationField.value);
    } else if (wootifyVariantField && wootifyVariantField.value) {
      // Wootify custom variant: send parent product_id + wootify fields for CartBridge
      formData.set('product_id', productId);
      formData.set('wootify_variant_id', wootifyVariantField.value);
      var wootifyCustomValues = form.querySelector('[name="wootify_custom_values"]');
      if (wootifyCustomValues && wootifyCustomValues.value) {
        formData.set('wootify_custom_values', wootifyCustomValues.value);
      }
    } else {
      formData.set('product_id', productId);
    }

    requestWooEndpoint('add_to_cart', formData)
      .then(function (response) {
        setAddButtonState(state.activeAddButton, false);

        if (response && response.error && response.product_url) {
          state.productSubmitInFlight = false;
          window.location.href = response.product_url;
          return;
        }

        if (response && response.fragments) {
          refreshFromResponse(response, true);
          state.activeAddButton = null;
          state.productSubmitInFlight = false;
          return;
        }

        return refreshDrawerFragments(true).finally(function () {
          state.activeAddButton = null;
          state.productSubmitInFlight = false;
        });
      })
      .catch(function () {
        setAddButtonState(state.activeAddButton, false);
        state.activeAddButton = null;
        state.productSubmitInFlight = false;
      });
  }

  function handleSingleProductSubmit(event) {
    var form = event.target;

    if (!shouldHandleSingleProductForm(form)) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    if (typeof event.stopImmediatePropagation === 'function') {
      event.stopImmediatePropagation();
    }

    performSingleProductAddToCart(form, event.submitter || form.querySelector('.single_add_to_cart_button'));
  }

  function handleSingleProductButtonClick(event) {
    var button = event.target.closest('.single-product form.cart .single_add_to_cart_button');

    if (!button) {
      return;
    }

    var form = button.form || button.closest('form');

    if (!shouldHandleSingleProductForm(form)) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    if (typeof event.stopImmediatePropagation === 'function') {
      event.stopImmediatePropagation();
    }

    performSingleProductAddToCart(form, button);
  }

  function updateQuantity(cartItemKey, quantity) {
    if (state.busy) {
      return Promise.resolve(null);
    }

    var item = document.querySelector('.starterkit-cart-drawer__item[data-cart-item-key="' + cartItemKey + '"]');
    setBusy(true, item);

    return request('starterkit_update_cart_item', {
      action: 'starterkit_update_cart_item',
      nonce: starterkitCartDrawer.nonce,
      cart_item_key: cartItemKey,
      quantity: quantity,
    }).then(function (response) {
      if (!response || !response.success) {
        throw new Error('Cart update failed');
      }

      var refreshed = response.data ? refreshFromResponse(response.data, true) : false;

      if (!refreshed) {
        return refreshDrawerFragments(true);
      }
    }).catch(function () {
      return refreshDrawerFragments(true).finally(function () {
        var errorMessage = starterkitCartDrawer.i18n && starterkitCartDrawer.i18n.error ? starterkitCartDrawer.i18n.error : '';
        setStatus(errorMessage, true);
        showToast(errorMessage, true);
      });
    }).finally(function () {
      setBusy(false, null);
    });
  }

  function removeItem(cartItemKey) {
    if (state.busy) {
      return Promise.resolve(null);
    }

    var item = document.querySelector('.starterkit-cart-drawer__item[data-cart-item-key="' + cartItemKey + '"]');
    setBusy(true, item);

    return request('starterkit_remove_cart_item', {
      action: 'starterkit_remove_cart_item',
      nonce: starterkitCartDrawer.nonce,
      cart_item_key: cartItemKey,
    }).then(function (response) {
      if (!response || !response.success) {
        throw new Error('Cart remove failed');
      }

      var refreshed = response.data ? refreshFromResponse(response.data, true) : false;

      if (!refreshed) {
        return refreshDrawerFragments(true);
      }
    }).catch(function () {
      return refreshDrawerFragments(true).finally(function () {
        var errorMessage = starterkitCartDrawer.i18n && starterkitCartDrawer.i18n.error ? starterkitCartDrawer.i18n.error : '';
        setStatus(errorMessage, true);
        showToast(errorMessage, true);
      });
    }).finally(function () {
      setBusy(false, null);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    syncRefs();
    bindWooEvents();

    document.addEventListener('click', function (event) {
      var openTrigger = event.target.closest('.header-cart-link, .header-cart-button, [data-cart-drawer-open]');
      var closeTrigger = event.target.closest('[data-cart-drawer-close]');
      var qtyButton = event.target.closest('.starterkit-cart-drawer__qty-button');
      var removeButton = event.target.closest('.starterkit-cart-drawer__remove');
      var upsellAddButton = event.target.closest('.starterkit-cart-drawer__upsell-add');

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

        if (state.busy) {
          return;
        }

        var item = qtyButton.closest('.starterkit-cart-drawer__item');
        var currentQuantity = item ? parseInt(item.getAttribute('data-quantity') || '1', 10) : 1;
        var delta = parseInt(qtyButton.getAttribute('data-quantity-delta') || '0', 10);
        var nextQuantity = Math.max(0, currentQuantity + delta);
        var cartItemKey = qtyButton.getAttribute('data-cart-item-key') || '';

        if (cartItemKey) {
          updateQuantity(cartItemKey, nextQuantity);
        }

        return;
      }

      if (removeButton) {
        event.preventDefault();

        if (state.busy) {
          return;
        }

        var removeKey = removeButton.getAttribute('data-cart-item-key') || '';

        if (removeKey) {
          removeItem(removeKey);
        }

        return;
      }

      if (upsellAddButton) {
        event.preventDefault();

        if (upsellAddButton.classList.contains('is-loading')) {
          return;
        }

        addProductToCart(upsellAddButton);
      }
    });

    document.addEventListener('click', handleSingleProductButtonClick, true);
    document.addEventListener('submit', handleSingleProductSubmit, true);

    document.addEventListener('click', function (event) {
      var ajaxAddButton = event.target.closest('.ajax_add_to_cart');

      if (!ajaxAddButton || ajaxAddButton.closest('.starterkit-cart-drawer')) {
        return;
      }

      state.activeAddButton = ajaxAddButton;
      setAddButtonState(ajaxAddButton, true);
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeDrawer();
      }
    });
  });
})();
