(function () {
  var state = {
    drawer: null,
    panel: null,
    sheet: null,
    sheetContent: null,
    busy: false,
    activeAddButton: null,
    activeUpsellConfig: null,
    activeUpsellTrigger: null,
    sheetPreviousFocus: null,
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
    state.sheet = state.drawer ? state.drawer.querySelector('.starterkit-cart-drawer__sheet') : null;
    state.sheetContent = state.sheet ? state.sheet.querySelector('.starterkit-cart-drawer__sheet-content') : null;
  }

  function ensureUpsellSheet() {
    syncRefs();

    if (!state.panel) {
      return false;
    }

    if (!state.sheet) {
      state.panel.insertAdjacentHTML(
        'beforeend',
        '<div class="starterkit-cart-drawer__sheet" role="dialog" aria-modal="true" aria-label="' +
          escapeHtml(getI18n('chooseOptions', 'Choose options')) +
          '" aria-hidden="true">' +
          '<button type="button" class="starterkit-cart-drawer__sheet-overlay" data-cart-drawer-sheet-close aria-label="' +
          escapeHtml(getI18n('closeOptions', 'Close product options')) +
          '"></button>' +
          '<div class="starterkit-cart-drawer__sheet-panel">' +
            '<div class="starterkit-cart-drawer__sheet-content"></div>' +
          '</div>' +
        '</div>'
      );

      syncRefs();
    }

    return !!(state.sheet && state.sheetContent);
  }

  function getI18n(key, fallback) {
    if (
      typeof starterkitCartDrawer !== 'undefined' &&
      starterkitCartDrawer.i18n &&
      Object.prototype.hasOwnProperty.call(starterkitCartDrawer.i18n, key)
    ) {
      return starterkitCartDrawer.i18n[key];
    }

    return fallback || '';
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function decodeHtmlEntities(value) {
    return String(value || '')
      .replace(/&quot;/g, '"')
      .replace(/&#039;/g, "'")
      .replace(/&lt;/g, '<')
      .replace(/&gt;/g, '>')
      .replace(/&amp;/g, '&');
  }

  function parseConfigJson(value) {
    var raw = String(value || '{}');

    try {
      return JSON.parse(raw);
    } catch (_error) {
      try {
        return JSON.parse(decodeHtmlEntities(raw));
      } catch (_decodedError) {
        return null;
      }
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

    if (state.shouldHighlightOnOpen) {
      highlightPendingItem();
      state.shouldHighlightOnOpen = false;
    }
  }

  function closeUpsellSheet(clearContent, restoreFocus) {
    syncRefs();

    if (!state.sheet) {
      return;
    }

    var focusTarget = state.activeUpsellTrigger || state.sheetPreviousFocus;

    state.sheet.classList.remove('is-open');
    state.sheet.setAttribute('aria-hidden', 'true');
    state.activeUpsellConfig = null;
    state.activeUpsellTrigger = null;
    state.sheetPreviousFocus = null;

    if (clearContent !== false && state.sheetContent) {
      state.sheetContent.innerHTML = '';
    }

    if (restoreFocus !== false && focusTarget && document.contains(focusTarget) && typeof focusTarget.focus === 'function') {
      window.setTimeout(function () {
        focusTarget.focus();
      }, 30);
    }
  }

  function closeDrawer() {
    syncRefs();

    if (!state.drawer) {
      return;
    }

    closeUpsellSheet(true, false);
    state.drawer.classList.remove('is-open');
    state.drawer.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('has-cart-drawer');
    document.body.classList.remove('has-cart-drawer');
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
      setStatus(getI18n('updating', ''), false);
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

  function performAddToCartRequest(formData, button, options) {
    if (!formData) {
      return Promise.resolve(null);
    }

    options = options || {};
    prepareAddHighlight();
    state.activeAddButton = button || null;
    setAddButtonState(button, true);

    return requestWooEndpoint('add_to_cart', formData)
      .then(function (response) {
        setAddButtonState(button, false);
        state.activeAddButton = null;

        if (response && response.error && response.product_url) {
          window.location.href = response.product_url;
          return response;
        }

        if (options.closeSheetOnSuccess) {
          closeUpsellSheet();
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

        var errorMessage = getI18n('error', '');
        setStatus(errorMessage, true);
        showToast(errorMessage, true);

        if (typeof options.onError === 'function') {
          options.onError(errorMessage);
        }

        return null;
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

    return performAddToCartRequest(formData, button);
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
    formData.set('quantity', quantityField && quantityField.value ? quantityField.value : '1');

    if (wcVariationField && wcVariationField.value) {
      formData.set('product_id', wcVariationField.value);
    } else if (wootifyVariantField && wootifyVariantField.value) {
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
    })
      .then(function (response) {
        if (!response || !response.success) {
          throw new Error('Cart update failed');
        }

        var refreshed = response.data ? refreshFromResponse(response.data, true) : false;

        if (!refreshed) {
          return refreshDrawerFragments(true);
        }
      })
      .catch(function () {
        return refreshDrawerFragments(true).finally(function () {
          var errorMessage = getI18n('error', '');
          setStatus(errorMessage, true);
          showToast(errorMessage, true);
        });
      })
      .finally(function () {
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
    })
      .then(function (response) {
        if (!response || !response.success) {
          throw new Error('Cart remove failed');
        }

        var refreshed = response.data ? refreshFromResponse(response.data, true) : false;

        if (!refreshed) {
          return refreshDrawerFragments(true);
        }
      })
      .catch(function () {
        return refreshDrawerFragments(true).finally(function () {
          var errorMessage = getI18n('error', '');
          setStatus(errorMessage, true);
          showToast(errorMessage, true);
        });
      })
      .finally(function () {
        setBusy(false, null);
      });
  }

  function getUpsellSelectorConfig(button) {
    if (!button) {
      return null;
    }

    var selector = button.getAttribute('data-config-selector') || '';

    if (!selector) {
      return null;
    }

    var node = document.querySelector(selector);

    if (!node) {
      return null;
    }

    return parseConfigJson(node.textContent || '{}');
  }

  function buildUpsellSheetMarkup(config) {
    var image = config.image || {};
    var headerImage = image.src
      ? '<img src="' + escapeHtml(image.src) + '" alt="' + escapeHtml(image.alt || config.name || '') + '">'
      : '';
    var attributeMarkup = (config.attributes || [])
      .map(function (attribute, index) {
        var fieldId = 'starterkit-cart-drawer-option-' + String(config.productId || 'product') + '-' + String(index);
        var attributeLabel = String(attribute.label || '');
        var defaultValue =
          config.defaultAttributes && Object.prototype.hasOwnProperty.call(config.defaultAttributes, attribute.name)
            ? String(config.defaultAttributes[attribute.name] || '')
            : '';
        var optionsMarkup = (attribute.options || [])
          .map(function (option) {
            var optionValue = Object.prototype.hasOwnProperty.call(option, 'value') ? String(option.value) : '';
            var optionLabel = Object.prototype.hasOwnProperty.call(option, 'label') ? String(option.label) : optionValue;
            var selected = defaultValue && defaultValue === optionValue ? ' selected' : '';

            return (
              '<option value="' +
              escapeHtml(optionValue) +
              '" data-option-label="' +
              escapeHtml(optionLabel) +
              '"' +
              selected +
              '>' +
              escapeHtml(optionLabel) +
              '</option>'
            );
          })
          .join('');

        return (
          '<div class="starterkit-cart-drawer__selector-group">' +
          '<label class="starterkit-cart-drawer__selector-label" for="' +
          escapeHtml(fieldId) +
          '">' +
          escapeHtml(attributeLabel) +
          '</label>' +
          '<div class="starterkit-cart-drawer__selector-control">' +
          '<select id="' +
          escapeHtml(fieldId) +
          '" class="starterkit-cart-drawer__selector-select" data-attribute-name="' +
          escapeHtml(attribute.name || '') +
          '" data-attribute-label="' +
          escapeHtml(attributeLabel) +
          '">' +
          '<option value="">' +
          escapeHtml(getI18n('selectOption', 'Select') + ' ' + (attributeLabel || 'option')) +
          '</option>' +
          optionsMarkup +
          '</select>' +
          '</div>' +
          '</div>'
        );
      })
      .join('');

    return (
      '<div class="starterkit-cart-drawer__sheet-inner">' +
      '<div class="starterkit-cart-drawer__sheet-header">' +
      '<button type="button" class="starterkit-cart-drawer__sheet-back" data-cart-drawer-sheet-close aria-label="' +
      escapeHtml(getI18n('back', 'Back')) +
      '">' +
      '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">' +
      '<path d="M12.5 4.5 7 10l5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>' +
      '</svg>' +
      '</button>' +
      '<div class="starterkit-cart-drawer__sheet-heading">' +
      '<h3>' +
      escapeHtml(getI18n('chooseOptions', 'Choose options')) +
      '</h3>' +
      '</div>' +
      '</div>' +
      '<div class="starterkit-cart-drawer__sheet-body">' +
      '<div class="starterkit-cart-drawer__sheet-product">' +
      '<div class="starterkit-cart-drawer__sheet-media" data-sheet-image>' +
      headerImage +
      '</div>' +
      '<div class="starterkit-cart-drawer__sheet-copy">' +
      '<a class="starterkit-cart-drawer__sheet-title" href="' +
      escapeHtml(config.permalink || '#') +
      '">' +
      escapeHtml(config.name || '') +
      '</a>' +
      '<div class="starterkit-cart-drawer__sheet-price" data-sheet-price>' +
      String(config.priceHtml || '') +
      '</div>' +
      '<div class="starterkit-cart-drawer__sheet-availability" data-sheet-availability></div>' +
      '</div>' +
      '</div>' +
      '<form class="starterkit-cart-drawer__selector-form" novalidate>' +
      attributeMarkup +
      '<div class="starterkit-cart-drawer__sheet-actions">' +
      '<div class="starterkit-cart-drawer__selector-message" data-sheet-message aria-live="polite"></div>' +
      '<button type="submit" class="button button-primary starterkit-cart-drawer__sheet-submit" disabled>' +
      escapeHtml(getI18n('confirmAdd', 'Add to cart')) +
      '</button>' +
      '</div>' +
      '</form>' +
      '</div>' +
      '</div>'
    );
  }

  function getUpsellSheetForm() {
    syncRefs();

    if (!state.sheetContent) {
      return null;
    }

    return state.sheetContent.querySelector('.starterkit-cart-drawer__selector-form');
  }

  function getUpsellFormSelections(form) {
    var selection = {};

    if (!form) {
      return selection;
    }

    form.querySelectorAll('.starterkit-cart-drawer__selector-select').forEach(function (select) {
      var attributeName = select.getAttribute('data-attribute-name') || '';
      selection[attributeName] = select.value || '';
    });

    return selection;
  }

  function variationMatchesSelection(variation, selection, attributes, requireComplete) {
    return (attributes || []).every(function (attribute) {
      var attributeName = attribute.name || '';
      var selectedValue = selection[attributeName] || '';
      var variationValue =
        variation && variation.attributes && Object.prototype.hasOwnProperty.call(variation.attributes, attributeName)
          ? variation.attributes[attributeName] || ''
          : '';

      if (requireComplete && !selectedValue) {
        return false;
      }

      if (!selectedValue) {
        return true;
      }

      if (!variationValue) {
        return true;
      }

      return variationValue === selectedValue;
    });
  }

  function getMatchingVariations(config, selection, requireComplete) {
    return (config.variations || []).filter(function (variation) {
      return variationMatchesSelection(variation, selection, config.attributes || [], requireComplete);
    });
  }

  function isVariationAvailable(variation) {
    if (!variation) {
      return false;
    }

    if (variation.variationIsActive === false) {
      return false;
    }

    if (variation.isPurchasable === false) {
      return false;
    }

    return variation.isInStock !== false;
  }

  function findExactVariation(config, selection) {
    var matches = getMatchingVariations(config, selection, true);

    return matches.length ? matches[0] : null;
  }

  function isUpsellOptionAvailable(config, selection, attributeName, optionValue) {
    var nextSelection = {};

    Object.keys(selection || {}).forEach(function (key) {
      nextSelection[key] = selection[key];
    });

    nextSelection[attributeName] = optionValue;

    return getMatchingVariations(config, nextSelection, false).some(isVariationAvailable);
  }

  function setUpsellSheetMessage(form, message, isError) {
    if (!form) {
      return;
    }

    var messageNode = form.querySelector('[data-sheet-message]');

    if (!messageNode) {
      return;
    }

    messageNode.textContent = message || '';
    messageNode.classList.toggle('is-visible', !!message);
    messageNode.classList.toggle('is-error', !!message && !!isError);
  }

  function updateUpsellSheetSummary(form, config, variation) {
    syncRefs();

    if (!state.sheetContent) {
      return;
    }

    var mediaNode = state.sheetContent.querySelector('[data-sheet-image]');
    var priceNode = state.sheetContent.querySelector('[data-sheet-price]');
    var availabilityNode = state.sheetContent.querySelector('[data-sheet-availability]');
    var submitButton = form ? form.querySelector('.starterkit-cart-drawer__sheet-submit') : null;
    var fallbackImage = config.image || {};
    var nextImage = variation && variation.image && variation.image.src ? variation.image : fallbackImage;

    if (mediaNode) {
      mediaNode.innerHTML = nextImage && nextImage.src
        ? '<img src="' + escapeHtml(nextImage.src) + '" alt="' + escapeHtml(nextImage.alt || config.name || '') + '">'
        : '';
    }

    if (priceNode) {
      priceNode.innerHTML = variation && variation.priceHtml ? String(variation.priceHtml) : String(config.priceHtml || '');
    }

    if (availabilityNode) {
      availabilityNode.innerHTML = variation && variation.availabilityHtml ? String(variation.availabilityHtml) : '';
    }

    if (!submitButton) {
      return;
    }

    if (!variation) {
      submitButton.disabled = true;
      setUpsellSheetMessage(form, '', false);
      return;
    }

    if (!isVariationAvailable(variation)) {
      submitButton.disabled = true;
      setUpsellSheetMessage(form, getI18n('unavailableVariation', 'This combination is currently unavailable.'), true);
      return;
    }

    submitButton.disabled = false;
    setUpsellSheetMessage(form, '', false);
  }

  function syncUpsellSheetSelection(form) {
    var config = state.activeUpsellConfig;

    if (!form || !config || !(config.attributes || []).length) {
      return;
    }

    var changed = true;
    var guard = 0;
    var unavailableLabel = getI18n('unavailableOption', 'Unavailable');

    while (changed && guard < (config.attributes || []).length + 1) {
      changed = false;
      guard += 1;

      var currentSelection = getUpsellFormSelections(form);

      (config.attributes || []).forEach(function (attribute) {
        var select = form.querySelector('.starterkit-cart-drawer__selector-select[data-attribute-name="' + attribute.name + '"]');

        if (!select) {
          return;
        }

        Array.prototype.forEach.call(select.options, function (option) {
          if (!option.value) {
            option.disabled = false;
            return;
          }

          var baseLabel = option.getAttribute('data-option-label') || option.textContent || '';
          var disabled = !isUpsellOptionAvailable(config, currentSelection, attribute.name, option.value);

          option.disabled = disabled;
          option.textContent = disabled ? baseLabel + ' - ' + unavailableLabel : baseLabel;
        });

        select.classList.toggle('has-value', !!select.value);

        var selectedOption = select.options[select.selectedIndex];

        if (selectedOption && selectedOption.value && selectedOption.disabled) {
          select.value = '';
          changed = true;
        }
      });
    }

    var finalSelection = getUpsellFormSelections(form);
    var exactVariation = findExactVariation(config, finalSelection);
    updateUpsellSheetSummary(form, config, exactVariation);
  }

  function getFocusableSheetElements() {
    var root = state.sheetContent || state.sheet;

    if (!root) {
      return [];
    }

    return Array.prototype.filter.call(
      root.querySelectorAll('a[href], button:not([disabled]), select:not([disabled]), textarea:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'),
      function (node) {
        return !!(node.offsetWidth || node.offsetHeight || node.getClientRects().length);
      }
    );
  }

  function trapUpsellSheetFocus(event) {
    if (!state.sheet || !state.sheet.classList.contains('is-open') || event.key !== 'Tab') {
      return;
    }

    var focusable = getFocusableSheetElements();

    if (!focusable.length) {
      event.preventDefault();
      return;
    }

    var first = focusable[0];
    var last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
      return;
    }

    if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function openUpsellSheet(button) {
    if (!ensureUpsellSheet()) {
      return;
    }

    var config = getUpsellSelectorConfig(button);

    if (!config) {
      var fallbackUrl = button ? button.getAttribute('data-product_url') || '' : '';

      if (fallbackUrl) {
        window.location.href = fallbackUrl;
      }

      return;
    }

    state.activeUpsellConfig = config;
    state.activeUpsellTrigger = button;
    state.sheetPreviousFocus = document.activeElement;
    state.sheetContent.innerHTML = buildUpsellSheetMarkup(config);
    state.sheet.classList.add('is-open');
    state.sheet.setAttribute('aria-hidden', 'false');
    openDrawer();

    var form = getUpsellSheetForm();
    syncUpsellSheetSelection(form);

    if (form) {
      var firstField = form.querySelector('.starterkit-cart-drawer__selector-select');
      if (firstField) {
        window.setTimeout(function () {
          firstField.focus();
        }, 30);
      }
    }
  }

  function handleUpsellSheetSubmit(form) {
    if (!form || !state.activeUpsellConfig || state.busy) {
      return;
    }

    syncUpsellSheetSelection(form);

    var selection = getUpsellFormSelections(form);
    var variation = findExactVariation(state.activeUpsellConfig, selection);

    if (!variation) {
      setUpsellSheetMessage(form, getI18n('chooseAllOptions', 'Please choose product options before adding to cart.'), true);
      return;
    }

    if (!isVariationAvailable(variation)) {
      setUpsellSheetMessage(form, getI18n('unavailableVariation', 'This combination is currently unavailable.'), true);
      return;
    }

    var submitButton = form.querySelector('.starterkit-cart-drawer__sheet-submit');
    var formData = new FormData();
    var quantity = variation.minQty && variation.minQty > 0 ? String(variation.minQty) : '1';

    formData.set('quantity', quantity);

    if (state.activeUpsellConfig.isWootify) {
      formData.set('product_id', String(state.activeUpsellConfig.productId || ''));
      formData.set('wootify_variant_id', String(variation.variationId || ''));
      formData.set('wootify_selected_attributes', JSON.stringify(selection || {}));
    } else {
      formData.set('product_id', String(variation.variationId || state.activeUpsellConfig.productId || ''));
    }

    performAddToCartRequest(formData, submitButton, {
      closeSheetOnSuccess: true,
      onError: function (message) {
        setUpsellSheetMessage(form, message, true);
      },
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    syncRefs();
    bindWooEvents();

    document.addEventListener('click', function (event) {
      var openTrigger = event.target.closest('.header-cart-link, .header-cart-button, [data-cart-drawer-open]');
      var closeTrigger = event.target.closest('[data-cart-drawer-close]');
      var sheetCloseTrigger = event.target.closest('[data-cart-drawer-sheet-close]');
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

      if (sheetCloseTrigger) {
        event.preventDefault();
        closeUpsellSheet();
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

        if (upsellAddButton.getAttribute('data-config-selector')) {
          openUpsellSheet(upsellAddButton);
          return;
        }

        addProductToCart(upsellAddButton);
      }
    });

    document.addEventListener('change', function (event) {
      var selectorField = event.target.closest('.starterkit-cart-drawer__selector-select');

      if (!selectorField) {
        return;
      }

      var form = selectorField.closest('.starterkit-cart-drawer__selector-form');
      syncUpsellSheetSelection(form);
    });

    document.addEventListener('submit', function (event) {
      var selectorForm = event.target.closest('.starterkit-cart-drawer__selector-form');

      if (!selectorForm) {
        return;
      }

      event.preventDefault();
      handleUpsellSheetSubmit(selectorForm);
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
      trapUpsellSheetFocus(event);

      if (event.defaultPrevented) {
        return;
      }

      if (event.key !== 'Escape') {
        return;
      }

      if (state.sheet && state.sheet.classList.contains('is-open')) {
        closeUpsellSheet();
        return;
      }

      closeDrawer();
    });
  });
})();
