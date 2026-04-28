(function() {
  if (window.top === window.self) {
    return;
  }

  var selectedZoneId = '';
  var selectedElementId = '';
  var dragState = null;
  var dropMarker = null;
  var layoutSettings = {};
  var previewContext = new URLSearchParams(window.location.search).get('starterkit_builder_context') || 'master';
  var parentOrigin = getParentOrigin();

  function getParentOrigin() {
    try {
      if (document.referrer) {
        return new URL(document.referrer).origin;
      }
    } catch (error) {
      return window.location.origin;
    }

    return window.location.origin;
  }

  function isParentMessage(event) {
    return event.source === window.parent && event.origin === parentOrigin;
  }

  function notifyParent(payload) {
    window.parent.postMessage(payload, parentOrigin);
  }

  function cssEscape(value) {
    return String(value).replace(/["\\]/g, '\\$&');
  }

  function clearDropClasses() {
    document
      .querySelectorAll('.starterkit-builder-zone.is-builder-droppable, .starterkit-builder-zone.is-builder-drop-invalid, .starterkit-builder-element.is-drop-before, .starterkit-builder-element.is-drop-after')
      .forEach(function(node) {
        node.classList.remove('is-builder-droppable', 'is-builder-drop-invalid', 'is-drop-before', 'is-drop-after');
      });
  }

  function ensureDropMarker() {
    if (dropMarker) {
      return dropMarker;
    }

    dropMarker = document.createElement('div');
    dropMarker.className = 'starterkit-builder-drop-marker';
    document.body.appendChild(dropMarker);
    return dropMarker;
  }

  function hideDropMarker() {
    if (dropMarker) {
      dropMarker.style.display = 'none';
    }
  }

  function clearSelection() {
    document
      .querySelectorAll('.starterkit-builder-zone.is-builder-selected, .starterkit-builder-element.is-builder-selected')
      .forEach(function(node) {
        node.classList.remove('is-builder-selected');
      });
  }

  function isInteractiveZone(zone) {
    if (!zone) {
      return false;
    }

    var zoneContext = zone.getAttribute('data-builder-zone-context') || 'master';

    return zoneContext === previewContext;
  }

  function stripInactiveMasterZones() {
    if (previewContext === 'master') {
      return;
    }

    document
      .querySelectorAll('[data-builder-zone-context="master"]')
      .forEach(function(zone) {
        zone.classList.remove(
          'starterkit-builder-zone',
          'starterkit-builder-zone--empty',
          'is-builder-selected',
          'is-builder-droppable',
          'is-builder-drop-invalid'
        );
      });
  }

  function applySelection(zoneId, elementId) {
    clearSelection();
    selectedZoneId = zoneId || '';
    selectedElementId = elementId || '';

    if (selectedZoneId) {
      var zone = document.querySelector('[data-builder-zone="' + cssEscape(selectedZoneId) + '"]');
      if (zone && isInteractiveZone(zone)) {
        zone.classList.add('is-builder-selected');
      }
    }

    if (selectedElementId) {
      var element = document.querySelector('[data-builder-element-id="' + cssEscape(selectedElementId) + '"]');
      if (element && isInteractiveZone(element.closest('[data-builder-zone]'))) {
        element.classList.add('is-builder-selected');
      }
    }
  }

  function applyDragState(nextDrag) {
    dragState = nextDrag || null;
    clearDropClasses();
    hideDropMarker();
  }

  function syncZonePlaceholder(zone) {
    if (!zone) {
      return;
    }

    var hasElements = !!zone.querySelector('[data-builder-element-id]');
    var placeholder = zone.querySelector('.starterkit-builder-zone__placeholder');

    zone.classList.toggle('starterkit-builder-zone--empty', !hasElements);

    if (hasElements) {
      if (placeholder) {
        placeholder.remove();
      }
      return;
    }

    if (placeholder) {
      return;
    }

    placeholder = document.createElement('div');
    placeholder.className = 'starterkit-builder-zone__placeholder';

    var title = document.createElement('h3');
    title.textContent = zone.getAttribute('data-builder-zone-label') || 'Zone';

    var description = document.createElement('span');
    description.textContent = 'Drop elements here from the builder panel.';

    placeholder.appendChild(title);
    placeholder.appendChild(description);
    zone.appendChild(placeholder);
  }

  function removeElementFromPreview(zoneId, elementId) {
    if (!zoneId || !elementId) {
      return;
    }

    var zone = document.querySelector('[data-builder-zone="' + cssEscape(zoneId) + '"]');
    var element = document.querySelector('[data-builder-element-id="' + cssEscape(elementId) + '"]');

    if (!zone || !element || !zone.contains(element)) {
      return;
    }

    if (selectedElementId === elementId) {
      selectedElementId = '';
    }

    element.remove();
    syncZonePlaceholder(zone);
    applySelection(selectedZoneId === zoneId ? zoneId : '', '');
  }

  function buildOptimisticElement(instanceId, elementType, label) {
    var wrapper = document.createElement('div');
    wrapper.className = 'starterkit-element-wrapper starterkit-element-wrapper--' + String(elementType || 'element').replace(/[^a-z0-9_-]/gi, '-').toLowerCase() + ' starterkit-builder-element starterkit-builder-element--optimistic';
    wrapper.setAttribute('data-builder-element-id', instanceId || '');
    wrapper.setAttribute('data-builder-element-type', elementType || '');

    var toolbar = document.createElement('div');
    toolbar.className = 'starterkit-builder-element__toolbar';
    toolbar.setAttribute('aria-hidden', 'false');
    toolbar.innerHTML =
      '<button type="button" class="starterkit-builder-element__move" draggable="true" data-builder-move-element="' + (instanceId || '') + '" aria-label="Move element">↕</button>' +
      '<button type="button" class="starterkit-builder-element__delete" data-builder-delete-element="' + (instanceId || '') + '" aria-label="Remove element">&times;</button>';

    var card = document.createElement('div');
    card.className = 'starterkit-element-card starterkit-builder-element-card--optimistic';

    var inner = document.createElement('div');
    inner.className = 'container starterkit-element-card__inner';

    var title = document.createElement('h3');
    title.className = 'starterkit-element-card__title';
    title.textContent = label || elementType || 'Element';

    inner.appendChild(title);
    card.appendChild(inner);
    wrapper.appendChild(toolbar);
    wrapper.appendChild(card);

    return wrapper;
  }

  function insertOptimisticElement(zoneId, instanceId, elementType, label, targetElementId, position) {
    if (!zoneId || !instanceId) {
      return;
    }

    var zone = document.querySelector('[data-builder-zone="' + cssEscape(zoneId) + '"]');
    if (!zone) {
      return;
    }

    var existing = zone.querySelector('[data-builder-element-id="' + cssEscape(instanceId) + '"]');
    if (existing) {
      applySelection(zoneId, instanceId);
      return;
    }

    var optimistic = buildOptimisticElement(instanceId, elementType, label);
    var target = targetElementId ? zone.querySelector('[data-builder-element-id="' + cssEscape(targetElementId) + '"]') : null;

    var placeholder = zone.querySelector('.starterkit-builder-zone__placeholder');
    if (placeholder) {
      placeholder.remove();
    }

    if (target && target.parentNode === zone) {
      if (position === 'before') {
        zone.insertBefore(optimistic, target);
      } else {
        zone.insertBefore(optimistic, target.nextSibling);
      }
    } else {
      zone.appendChild(optimistic);
    }

    syncZonePlaceholder(zone);
    applySelection(zoneId, instanceId);
  }

  function replaceZoneMarkup(zoneId, html) {
    if (!zoneId || !html) {
      return;
    }

    var currentZone = document.querySelector('[data-builder-zone="' + cssEscape(zoneId) + '"]');
    if (!currentZone) {
      return;
    }

    var wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();

    var nextZone = wrapper.firstElementChild;
    if (!nextZone) {
      return;
    }

    currentZone.replaceWith(nextZone);
    applySelection(selectedZoneId, selectedElementId);
  }

  function canDropOnZone(zoneId) {
    if (!dragState) {
      return false;
    }

    if (!Array.isArray(dragState.allowedZones)) {
      return true;
    }

    return dragState.allowedZones.indexOf(zoneId) !== -1;
  }

  function showDropMarker(target, position) {
    var marker = ensureDropMarker();
    var rect = target.getBoundingClientRect();
    marker.style.display = 'block';
    marker.style.position = 'fixed';
    marker.style.left = rect.left + 'px';
    marker.style.width = rect.width + 'px';
    marker.style.top = (position === 'before' ? rect.top : rect.bottom - 2) + 'px';
  }

  function applyHeaderOneSettings(settings) {
    var header = document.querySelector('.site-header--preset-1');
    if (!header) {
      return;
    }

    if (settings.header_1_logo_max_height !== undefined) {
      header.style.setProperty('--header-1-logo-max-height', String(settings.header_1_logo_max_height || 45) + 'px');
    }

    if (settings.header_1_header_min_height !== undefined) {
      header.style.setProperty('--header-1-min-height', String(settings.header_1_header_min_height || 72) + 'px');
    }

    if (settings.header_1_background_color) {
      header.style.setProperty('--header-1-bg', String(settings.header_1_background_color));
    }

    if (settings.header_1_navigation_gap !== undefined) {
      header.style.setProperty('--header-1-nav-gap', String(settings.header_1_navigation_gap || 28) + 'px');
    }
  }

  function applyHeaderTwoSettings(settings) {
    var header = document.querySelector('.site-header--preset-2');
    if (!header) {
      return;
    }

    header.classList.toggle(
      'site-header--not-sticky',
      String(settings.header_2_enable_sticky === undefined ? '1' : settings.header_2_enable_sticky) !== '1'
    );

    if (settings.header_2_logo_max_height !== undefined) {
      header.style.setProperty('--header-2-logo-max-height', String(settings.header_2_logo_max_height || 52) + 'px');
    }

    if (settings.header_2_navigation_gap !== undefined) {
      header.style.setProperty('--header-2-nav-gap', String(settings.header_2_navigation_gap || 28) + 'px');
    }

    if (settings.header_2_background_color) {
      header.style.setProperty('--header-2-bg', String(settings.header_2_background_color));
    }

    if (settings.header_2_navigation_background_color !== undefined) {
      header.style.setProperty('--header-2-nav-bg', String(settings.header_2_navigation_background_color || 'transparent'));
    }
  }

  function applyFooterOneSettings(settings) {
    var grid = document.querySelector('.site-footer--preset-1 .footer-grid--preset-1');
    if (!grid) {
      return;
    }

    var columns = Array.prototype.slice.call(grid.querySelectorAll(':scope > .footer-col'));
    var maxColumns = Math.max(1, Math.min(4, Number(settings.footer_1_column_count || 4)));
    var visibleCount = 0;

    columns.forEach(function(column, index) {
      var columnIndex = Number(column.getAttribute('data-footer-column-index') || (index + 1));
      var key = 'footer_1_show_column_' + columnIndex;
      var isVisible = columnIndex <= maxColumns && String(settings[key] === undefined ? '1' : settings[key]) === '1';
      column.style.display = isVisible ? '' : 'none';
      if (isVisible) {
        visibleCount += 1;
      }
    });

    if (!visibleCount && columns[0]) {
      columns[0].style.display = '';
      visibleCount = 1;
    }

    grid.style.setProperty('--footer-1-columns', String(visibleCount || 1));
  }

  function applyProductSplitLayoutSettings(settings) {
    var galleryWidth = Math.max(40, Math.min(70, Number(settings.product_gallery_column_ratio || 60)));
    var summaryWidth = 100 - galleryWidth;

    document
      .querySelectorAll('.starterkit-product-layout.product-layout-1 .starterkit-product-layout__product-shell, .starterkit-product-layout--preset-3 .starterkit-product-layout__product-shell--preset-3')
      .forEach(function(shell) {
        shell.style.setProperty('--starterkit-product-gallery-col', String(galleryWidth) + '%');
        shell.style.setProperty('--starterkit-product-summary-col', String(summaryWidth) + '%');
      });
  }

  function applyProductDescriptionTabSettings(settings) {
    var showDescription = String(settings.product_show_description_tab === undefined ? '1' : settings.product_show_description_tab) === '1';

    document
      .querySelectorAll('.description_tab, .wc-tab.description_tab')
      .forEach(function(tab) {
        tab.style.display = showDescription ? '' : 'none';
      });

    document
      .querySelectorAll('#tab-description, .woocommerce-Tabs-panel--description')
      .forEach(function(panel) {
        panel.style.display = showDescription ? '' : 'none';
      });
  }

  function applyProductLayoutOneRelatedSettings(settings) {
    var layout = document.querySelector('.starterkit-product-layout.product-layout-1');
    if (!layout) {
      return;
    }

    var showRelated = String(settings.product_layout_1_show_related_products === undefined ? '1' : settings.product_layout_1_show_related_products) === '1';
    var relatedCount = Math.max(1, Math.min(12, Number(settings.product_layout_1_related_products_count || 5)));
    var relatedColumns = Math.max(1, Math.min(6, Number(settings.product_layout_1_related_products_columns || 5)));
    var relatedSection = layout.querySelector('.related.products');

    layout.style.setProperty('--starterkit-related-products-columns', String(relatedColumns));

    if (!relatedSection) {
      return;
    }

    relatedSection.style.display = showRelated ? '' : 'none';

    relatedSection
      .querySelectorAll('.products > li.product')
      .forEach(function(item, index) {
        item.style.display = showRelated && index < relatedCount ? '' : 'none';
      });
  }

  function applyLayoutSettings(settings) {
    layoutSettings = Object.assign({}, layoutSettings, settings || {});
    applyHeaderOneSettings(layoutSettings);
    applyHeaderTwoSettings(layoutSettings);
    applyFooterOneSettings(layoutSettings);
    applyProductSplitLayoutSettings(layoutSettings);
    applyProductDescriptionTabSettings(layoutSettings);
    applyProductLayoutOneRelatedSettings(layoutSettings);
  }

  function replaceLayoutPartial(target, html) {
    if (!target || html === undefined || html === null) {
      return;
    }

    var current = document.querySelector(target);
    if (!current) {
      return;
    }

    var wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();

    var next = wrapper.firstElementChild;
    if (!next) {
      return;
    }

    current.replaceWith(next);
  }

  function blockPreviewNavigation(event) {
    if (event.defaultPrevented || !event.target.closest) {
      return;
    }

    var link = event.target.closest('a[href]');
    if (!link) {
      return;
    }

    event.preventDefault();
  }

  document.addEventListener(
    'click',
    function(event) {
      var deleteButton = event.target.closest('[data-builder-delete-element]');
      if (deleteButton) {
        var deleteElement = deleteButton.closest('[data-builder-element-id]');
        var deleteZone = deleteButton.closest('[data-builder-zone]');

        if (!deleteElement || !deleteZone || !isInteractiveZone(deleteZone)) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();

        notifyParent(
          {
            type: 'starterkit-builder-delete-element',
            zoneId: deleteZone.getAttribute('data-builder-zone') || '',
            context: deleteZone.getAttribute('data-builder-zone-context') || '',
            elementId: deleteElement.getAttribute('data-builder-element-id') || ''
          }
        );
        return;
      }

      var element = event.target.closest('[data-builder-element-id]');
      var zone = event.target.closest('[data-builder-zone]');

      if (!element && !zone) {
        return;
      }

      if (!isInteractiveZone(zone)) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();

      var zoneId = zone ? zone.getAttribute('data-builder-zone') : '';
      var zoneContext = zone ? zone.getAttribute('data-builder-zone-context') : '';
      var elementId = element ? element.getAttribute('data-builder-element-id') : '';

      applySelection(zoneId, elementId);

      notifyParent(
        {
          type: 'starterkit-builder-select',
          zoneId: zoneId,
          context: zoneContext,
          elementId: elementId
        }
      );
    },
    true
  );

  document.addEventListener('click', blockPreviewNavigation, true);

  document.addEventListener(
    'submit',
    function(event) {
      event.preventDefault();
    },
    true
  );

  document.addEventListener(
    'dragstart',
    function(event) {
      var moveButton = event.target.closest('[data-builder-move-element]');
      if (!moveButton) {
        return;
      }

      var moveElement = moveButton.closest('[data-builder-element-id]');
      var moveZone = moveButton.closest('[data-builder-zone]');

      if (!moveElement || !moveZone || !isInteractiveZone(moveZone)) {
        event.preventDefault();
        return;
      }

      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', moveElement.getAttribute('data-builder-element-id') || '');
      }

      notifyParent(
        {
          type: 'starterkit-builder-start-instance-drag',
          zoneId: moveZone.getAttribute('data-builder-zone') || '',
          context: moveZone.getAttribute('data-builder-zone-context') || '',
          elementId: moveElement.getAttribute('data-builder-element-id') || ''
        }
      );
    },
    true
  );

  document.addEventListener(
    'dragend',
    function() {
      notifyParent(
        {
          type: 'starterkit-builder-end-drag'
        }
      );
    },
    true
  );

  document.addEventListener(
    'dragover',
    function(event) {
      if (!dragState) {
        return;
      }

      var zone = event.target.closest('[data-builder-zone]');
      if (!zone) {
        clearDropClasses();
        hideDropMarker();
        return;
      }

      if (!isInteractiveZone(zone)) {
        clearDropClasses();
        hideDropMarker();
        return;
      }

      clearDropClasses();

      if (!canDropOnZone(zone.getAttribute('data-builder-zone'))) {
        zone.classList.add('is-builder-drop-invalid');
        hideDropMarker();
        return;
      }

      event.preventDefault();
      if (event.dataTransfer) {
        event.dataTransfer.dropEffect = dragState.type === 'library' ? 'copy' : 'move';
      }

      zone.classList.add('is-builder-droppable');

      var element = event.target.closest('[data-builder-element-id]');
      if (element && zone.contains(element)) {
        var rect = element.getBoundingClientRect();
        var midpoint = rect.top + rect.height / 2;
        var position = event.clientY < midpoint ? 'before' : 'after';
        element.classList.add(position === 'before' ? 'is-drop-before' : 'is-drop-after');
        showDropMarker(element, position);
      } else {
        hideDropMarker();
      }

      notifyParent(
        {
          type: 'starterkit-builder-hover-zone',
          zoneId: zone.getAttribute('data-builder-zone')
        }
      );
    },
    true
  );

  document.addEventListener(
    'drop',
    function(event) {
      if (!dragState) {
        return;
      }

      var zone = event.target.closest('[data-builder-zone]');
      if (!zone) {
        return;
      }

      if (!isInteractiveZone(zone)) {
        return;
      }

      if (!canDropOnZone(zone.getAttribute('data-builder-zone'))) {
        clearDropClasses();
        hideDropMarker();
        return;
      }

      event.preventDefault();

      var targetElement = event.target.closest('[data-builder-element-id]');
      var targetElementId = '';
      var position = 'after';

      if (targetElement && zone.contains(targetElement)) {
        var rect = targetElement.getBoundingClientRect();
        targetElementId = targetElement.getAttribute('data-builder-element-id') || '';
        position = event.clientY < rect.top + rect.height / 2 ? 'before' : 'after';
      }

      clearDropClasses();
      hideDropMarker();

      notifyParent(
        {
          type: 'starterkit-builder-drop',
          zoneId: zone.getAttribute('data-builder-zone'),
          targetElementId: targetElementId,
          position: position
        }
      );
    },
    true
  );

  document.addEventListener(
    'dragleave',
    function(event) {
      if (!dragState) {
        return;
      }

      if (event.target === document.documentElement || event.target === document.body) {
        clearDropClasses();
        hideDropMarker();
      }
    },
    true
  );

  window.addEventListener('message', function(event) {
    if (!event.data || !isParentMessage(event)) {
      return;
    }

    if (event.data.type === 'starterkit-builder-select') {
      applySelection(event.data.zoneId || '', event.data.elementId || '');
      return;
    }

    if (event.data.type === 'starterkit-builder-drag-state') {
      applyDragState(event.data.drag || null);
      return;
    }

    if (event.data.type === 'starterkit-builder-remove-element') {
      removeElementFromPreview(event.data.zoneId || '', event.data.elementId || '');
      return;
    }

    if (event.data.type === 'starterkit-builder-insert-element') {
      insertOptimisticElement(
        event.data.zoneId || '',
        event.data.instanceId || '',
        event.data.elementType || '',
        event.data.label || '',
        event.data.targetElementId || '',
        event.data.position || 'after'
      );
      return;
    }

    if (event.data.type === 'starterkit-builder-replace-zone') {
      replaceZoneMarkup(event.data.zoneId || '', event.data.html || '');
      return;
    }

    if (event.data.type === 'starterkit-builder-layout-settings') {
      applyLayoutSettings(event.data.settings || {});
      return;
    }

    if (event.data.type === 'starterkit-builder-replace-layout-partial') {
      replaceLayoutPartial(event.data.target || '', event.data.html || '');
    }
  });

  stripInactiveMasterZones();
  applyLayoutSettings(layoutSettings);
  notifyParent({ type: 'starterkit-builder-ready' });
})();
