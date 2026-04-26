(function() {
  var config = window.starterkitThemeBuilder || {};
  var bootstrap = config.bootstrap || {};
  var root = document.getElementById('starterkit-theme-builder-app');

  if (!root) {
    return;
  }

  var state = JSON.parse(JSON.stringify(bootstrap.state || {}));
  var stateVersion = bootstrap.version || '';
  var contexts = bootstrap.contexts || [];
  var previewUrls = bootstrap.previewUrls || {};
  var activeSchemas = bootstrap.activeSchemas || {};
  var elements = bootstrap.elements || {};
  var layoutSettings = JSON.parse(JSON.stringify(bootstrap.layoutSettings || {}));
  var layoutSettingsVersion = bootstrap.layoutSettingsVersion || '';
  var layoutSettingsSchemas = bootstrap.layoutSettingsSchemas || {};
  var exitUrl = config.exitUrl || '';
  var allowedMessageOrigins = getAllowedMessageOrigins();
  var colorPickerInitialized = false;
  var pendingLayoutPreviewPartials = {};

  var ui = {
    context: contexts[0] ? contexts[0].id : 'master',
    inspectorMode: (contexts[0] && contexts[0].id && contexts[0].id !== 'master') ? 'element' : 'settings',
    selectedContext: '',
    selectedZone: '',
    selectedElementId: '',
    deviceMode: 'desktop',
    search: '',
    drag: null,
    previewReloadTimer: null,
    layoutPreviewTimer: null,
    loading: false,
    builderDirty: false,
    layoutSettingsDirty: false,
    dirty: false,
    hasConflict: false,
    stateConflict: false,
    layoutSettingsConflict: false,
    error: ''
  };

  function getOriginFromUrl(url) {
    try {
      return new URL(url, window.location.href).origin;
    } catch (error) {
      return '';
    }
  }

  function getAllowedMessageOrigins() {
    var origins = [window.location.origin, config.adminOrigin || ''];

    Object.keys(previewUrls).forEach(function(context) {
      origins.push(getOriginFromUrl(previewUrls[context]));
    });

    return origins.filter(function(origin, index, list) {
      return origin && list.indexOf(origin) === index;
    });
  }

  function getPreviewIframe() {
    return root.querySelector('.starterkit-theme-builder__iframe');
  }

  function getPreviewTargetOrigin() {
    var iframe = getPreviewIframe();
    var src = iframe && iframe.src ? iframe.src : getIframeUrl();
    return getOriginFromUrl(src) || window.location.origin;
  }

  function isPreviewMessage(event) {
    var iframe = getPreviewIframe();

    if (!iframe || event.source !== iframe.contentWindow) {
      return false;
    }

    return allowedMessageOrigins.indexOf(event.origin) !== -1;
  }

  function request(action, payload) {
    var formData = new window.FormData();
    formData.append('action', action);
    formData.append('nonce', config.nonce || '');

    Object.keys(payload || {}).forEach(function(key) {
      formData.append(key, payload[key]);
    });

    return window
      .fetch(config.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
      .then(function(response) {
        return response.json().then(function(data) {
          if (!data || data.success !== true) {
            var message = data && data.data && data.data.message ? data.data.message : 'Builder request failed.';
            var error = new Error(message);
            error.payload = data && data.data ? data.data : null;
            throw error;
          }

          return data;
        });
      });
  }

  function requestZoneMarkup(zoneId, context) {
    if (!zoneId) {
      return Promise.resolve(null);
    }

    return request('starterkit_theme_builder_render_zone', {
      state: JSON.stringify(state),
      context: context || ui.context,
      zoneId: zoneId
    }).then(function(response) {
      return response && response.data ? response.data : null;
    });
  }

  function getContextSchemas(context) {
    return activeSchemas[context] || {};
  }

  function getZoneIndex(context) {
    var index = {};

    Object.keys(getContextSchemas(context)).forEach(function(presetId) {
      (getContextSchemas(context)[presetId].zones || []).forEach(function(zone) {
        index[zone.id] = Object.assign({}, zone, { presetId: presetId });
      });
    });

    return index;
  }

  function getSelectedContext() {
    return ui.selectedContext || ui.context;
  }

  function getZoneSchema(zoneId, context) {
    return getZoneIndex(context || ui.context)[zoneId] || null;
  }

  function getElementDefinition(elementId) {
    return elements[elementId] || null;
  }

  function getElementLimitMessage(elementId) {
    var definition = getElementDefinition(elementId);
    var label = definition && definition.label ? definition.label : elementId;
    var maxInstances = getElementMaxInstances(elementId);

    if (maxInstances === 1) {
      return label + ' can only be added once in this context.';
    }

    if (maxInstances > 1) {
      return label + ' can only be added ' + maxInstances + ' times in this context.';
    }

    return 'This element has reached its allowed limit in the current context.';
  }

  function getElementIconLabel(elementId) {
    var definition = getElementDefinition(elementId);
    var label = definition && definition.label ? definition.label : elementId;
    var parts = String(label)
      .replace(/[^a-z0-9\s]/gi, ' ')
      .trim()
      .split(/\s+/)
      .filter(Boolean);

    if (!parts.length) {
      return String(elementId || '?').slice(0, 2).toUpperCase();
    }

    if (parts.length === 1) {
      return parts[0].slice(0, 2).toUpperCase();
    }

    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
  }

  function getPresetState(context, presetId) {
    if (!state[context]) {
      state[context] = {};
    }

    if (!state[context][presetId]) {
      state[context][presetId] = {};
    }

    return state[context][presetId];
  }

  function getZoneItems(zoneId, context) {
    context = context || ui.context;
    var zone = getZoneSchema(zoneId, context);

    if (!zone) {
      return [];
    }

    var presetState = getPresetState(context, zone.presetId);

    if (!Array.isArray(presetState[zoneId])) {
      presetState[zoneId] = [];
    }

    return presetState[zoneId];
  }

  function getSelectedElement() {
    if (!ui.selectedZone || !ui.selectedElementId) {
      return null;
    }

    return getZoneItems(ui.selectedZone, getSelectedContext()).find(function(item) {
      return item.id === ui.selectedElementId;
    }) || null;
  }

  function getElementMaxInstances(elementId) {
    var definition = getElementDefinition(elementId);
    return definition && definition.max_instances ? Number(definition.max_instances) : 0;
  }

  function countElementInstances(elementId, excludeInstanceId, context) {
    var count = 0;
    context = context || ui.context;

    Object.keys(getContextSchemas(context)).forEach(function(presetId) {
      var presetState = getPresetState(context, presetId);

      Object.keys(presetState).forEach(function(zoneId) {
        (presetState[zoneId] || []).forEach(function(item) {
          if (item.type !== elementId) {
            return;
          }

          if (excludeInstanceId && item.id === excludeInstanceId) {
            return;
          }

          count += 1;
        });
      });
    });

    return count;
  }

  function syncSelectionWithState() {
    if (!ui.selectedZone) {
      ui.selectedElementId = '';
      return;
    }

    if (!getZoneSchema(ui.selectedZone, getSelectedContext())) {
      ui.selectedZone = '';
      ui.selectedElementId = '';
      ui.selectedContext = '';
      return;
    }

    if (!ui.selectedElementId) {
      return;
    }

    var stillExists = getZoneItems(ui.selectedZone, getSelectedContext()).some(function(item) {
      return item.id === ui.selectedElementId;
    });

    if (!stillExists) {
      ui.selectedElementId = '';
    }
  }

  function cloneValue(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function reloadLatestState() {
    ui.loading = true;
    ui.error = '';
    render();

    request('starterkit_theme_builder_bootstrap', {}).then(function(response) {
      if (!response || !response.data) {
        return;
      }

      state = cloneValue(response.data.state || {});
      stateVersion = response.data.version || '';
      layoutSettings = cloneValue(response.data.layoutSettings || {});
      layoutSettingsVersion = response.data.layoutSettingsVersion || '';
      ui.builderDirty = false;
      ui.layoutSettingsDirty = false;
      refreshDirtyState();
      ui.hasConflict = false;
      ui.stateConflict = false;
      ui.layoutSettingsConflict = false;

      if (response.data.previewUrls) {
        previewUrls = response.data.previewUrls;
        allowedMessageOrigins = getAllowedMessageOrigins();
      }

      if (response.data.activeSchemas) {
        activeSchemas = response.data.activeSchemas;
      }

      if (response.data.elements) {
        elements = response.data.elements;
      }

      if (response.data.layoutSettingsSchemas) {
        layoutSettingsSchemas = response.data.layoutSettingsSchemas;
      }

      syncSelectionWithState();
      schedulePreviewReload();
      scheduleLayoutPreviewUpdate();
    }).catch(function(error) {
      ui.error = error && error.message ? error.message : 'Failed to reload latest builder state.';
    }).finally(function() {
      ui.loading = false;
      render();
    });
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function makeInstance(elementId) {
    var definition = elements[elementId];
    return {
      id: 'tb_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8),
      type: elementId,
      enabled: true,
      settings: cloneValue(definition.default_settings || {})
    };
  }

  function getContextLabel(contextId) {
    if (contextId === 'master') {
      return 'Master';
    }

    if (contextId === 'product') {
      return 'Product Page';
    }

    if (contextId === 'archive') {
      return 'Archive / Shop';
    }

    var context = contexts.find(function(item) {
      return item.id === contextId;
    });

    return context && context.label ? context.label : contextId;
  }

  function getIframeUrl() {
    var url = new URL(previewUrls[ui.context] || previewUrls.master || window.location.origin);
    url.searchParams.set('starterkit_builder_device', ui.deviceMode);
    return url.toString();
  }

  function ensureShell() {
    if (root.getAttribute('data-builder-mounted') === '1') {
      return;
    }

    root.innerHTML =
      '<div class="js-builder-navbar"></div>' +
      '<div class="js-builder-sidebar"></div>' +
      '<div class="starterkit-theme-builder__preview-area">' +
      '<div class="starterkit-theme-builder__preview-toolbar js-builder-preview-toolbar"></div>' +
      '<div class="starterkit-theme-builder__preview-stage">' +
      '<div class="starterkit-theme-builder__preview-frame">' +
      '<iframe class="starterkit-theme-builder__iframe" src=""></iframe>' +
      '</div>' +
      '<div class="starterkit-theme-builder__preview-drop-layer" data-preview-drop-layer></div>' +
      '</div>' +
      '</div>' +
      '<div class="js-builder-inspector"></div>';

    root.querySelector('.starterkit-theme-builder__iframe').src = getIframeUrl();
    root.setAttribute('data-builder-mounted', '1');
  }

  function reloadPreview(forceReload) {
    var iframe = root.querySelector('.starterkit-theme-builder__iframe');
    var nextUrl = getIframeUrl();

    if (!iframe) {
      return;
    }

    if (iframe.src !== nextUrl) {
      iframe.src = nextUrl;
      return;
    }

    if (forceReload && iframe.contentWindow) {
      iframe.contentWindow.location.reload();
    }
  }

  function schedulePreviewReload() {
    if (ui.previewReloadTimer) {
      window.clearTimeout(ui.previewReloadTimer);
    }

    ui.previewReloadTimer = window.setTimeout(function() {
      ui.previewReloadTimer = null;
      reloadPreview(true);
    }, 180);
  }

  function refreshDirtyState() {
    ui.dirty = ui.builderDirty || ui.layoutSettingsDirty;
  }

  function markDirty(scope) {
    if (scope === 'layout') {
      ui.layoutSettingsDirty = true;
    } else {
      ui.builderDirty = true;
    }

    refreshDirtyState();
  }

  function syncPublishControls() {
    var publishButton = root.querySelector('[data-action="save-publish"]');
    var previewStatus = root.querySelector('.starterkit-theme-builder__preview-status span');

    if (publishButton) {
      publishButton.disabled = ui.loading || !ui.dirty || ui.hasConflict;
      publishButton.innerHTML = ui.loading ? 'Publishing...' : 'Save &amp; Publish';
    }

    if (previewStatus) {
      previewStatus.textContent = ui.loading ? 'Publishing...' : (ui.hasConflict ? 'Conflict detected' : (ui.dirty ? 'Unsaved changes' : 'Published'));
    }
  }

  function queueAllLayoutPreviewPartials() {
    Object.keys(layoutSettingsSchemas || {}).forEach(function(layoutId) {
      var schema = layoutSettingsSchemas[layoutId] || {};
      (schema.settings_schema || []).forEach(function(control) {
        if (control.preview_strategy === 'partial_render' && control.partial) {
          pendingLayoutPreviewPartials[control.partial] = true;
        }
      });
    });
  }

  function saveState() {
    if (ui.loading || !ui.dirty || ui.hasConflict) {
      return;
    }

    ui.loading = true;
    render();

    request('starterkit_theme_builder_save_state', {
      state: JSON.stringify(state),
      version: stateVersion,
      layoutSettings: JSON.stringify(layoutSettings),
      layoutSettingsVersion: layoutSettingsVersion,
      saveBuilderState: ui.builderDirty ? '1' : '0',
      saveLayoutSettings: ui.layoutSettingsDirty ? '1' : '0'
    }).then(function(response) {
      if (response && response.data && response.data.state) {
        state = response.data.state;
        stateVersion = response.data.version || stateVersion;
        layoutSettings = cloneValue(response.data.layoutSettings || layoutSettings);
        layoutSettingsVersion = response.data.layoutSettingsVersion || layoutSettingsVersion;
        if (response.data.savedBuilderState) {
          ui.builderDirty = false;
        }
        if (response.data.savedLayoutSettings) {
          ui.layoutSettingsDirty = false;
        }
        refreshDirtyState();
        ui.error = '';
        ui.hasConflict = false;
        ui.stateConflict = false;
        ui.layoutSettingsConflict = false;
        syncSelectionWithState();
        if (response.data.savedBuilderState) {
          schedulePreviewReload();
        } else if (response.data.savedLayoutSettings) {
          scheduleLayoutPreviewUpdate();
        }
      }
    }).catch(function(error) {
      var payload = error && error.payload ? error.payload : null;

      if (payload && payload.state && (!payload.stateConflict || payload.savedBuilderState)) {
        state = payload.state;
        stateVersion = payload.version || payload.stateVersion || stateVersion;
      }

      if (payload && payload.savedBuilderState) {
        ui.builderDirty = false;
      }

      if (payload && payload.serverVersion && payload.code !== 'layout_settings_version_conflict' && !payload.stateConflict) {
        stateVersion = payload.serverVersion;
      }

      if (payload && payload.layoutSettings && (!payload.layoutSettingsConflict || payload.savedLayoutSettings)) {
        layoutSettings = cloneValue(payload.layoutSettings);
      }

      if (payload && payload.savedLayoutSettings) {
        layoutSettingsVersion = payload.layoutSettingsVersion || payload.layoutServerVersion || layoutSettingsVersion;
        ui.layoutSettingsDirty = false;
      }

      if (error && error.message && /another session/i.test(error.message)) {
        ui.hasConflict = true;
        ui.stateConflict = !!(payload && payload.stateConflict);
        ui.layoutSettingsConflict = !!(payload && payload.layoutSettingsConflict);
      }

      refreshDirtyState();
      syncSelectionWithState();

      ui.error = error && error.message ? error.message : 'Failed to save builder state.';
    }).finally(function() {
      ui.loading = false;
      render();
    });
  }

  function selectZone(zoneId, context) {
    ui.inspectorMode = 'element';
    ui.selectedContext = context || ui.context;
    ui.selectedZone = zoneId || '';
    if (!zoneId) {
      ui.selectedElementId = '';
      ui.selectedContext = '';
    } else if (ui.selectedElementId) {
      var selectedExists = getZoneItems(zoneId, getSelectedContext()).some(function(item) {
        return item.id === ui.selectedElementId;
      });

      if (!selectedExists) {
        ui.selectedElementId = '';
      }
    }

    render();
    notifyPreviewSelection();
  }

  function selectElement(zoneId, elementId, context) {
    ui.inspectorMode = 'element';
    ui.selectedContext = context || ui.context;
    ui.selectedZone = zoneId || '';
    ui.selectedElementId = elementId || '';
    render();
    notifyPreviewSelection();
  }

  function notifyPreviewSelection() {
    var iframe = getPreviewIframe();
    if (!iframe || !iframe.contentWindow) {
      return;
    }

    iframe.contentWindow.postMessage(
      {
        type: 'starterkit-builder-select',
        zoneId: ui.selectedZone,
        elementId: ui.selectedElementId
      },
      getPreviewTargetOrigin()
    );
  }

  function notifyPreviewDragState() {
    var iframe = getPreviewIframe();
    if (!iframe || !iframe.contentWindow) {
      return;
    }

    iframe.contentWindow.postMessage(
      {
        type: 'starterkit-builder-drag-state',
        drag: getPreviewDragState()
      },
      getPreviewTargetOrigin()
    );
  }

  function notifyPreviewLayoutSettings() {
    var iframe = getPreviewIframe();
    if (!iframe || !iframe.contentWindow) {
      return;
    }

    iframe.contentWindow.postMessage(
      {
        type: 'starterkit-builder-layout-settings',
        settings: layoutSettings
      },
      getPreviewTargetOrigin()
    );
  }

  function notifyPreviewLayoutPartial(target, html) {
    var iframe = getPreviewIframe();
    if (!iframe || !iframe.contentWindow || !target || html === undefined || html === null) {
      return;
    }

    iframe.contentWindow.postMessage(
      {
        type: 'starterkit-builder-replace-layout-partial',
        target: target,
        html: html
      },
      getPreviewTargetOrigin()
    );
  }

  function refreshLayoutPartial(partial) {
    request('starterkit_theme_builder_render_layout_partial', {
      partial: partial,
      layoutSettings: JSON.stringify(layoutSettings)
    }).then(function(response) {
      var data = response && response.data ? response.data : null;
      if (!data || !data.target || data.html === undefined || data.html === null) {
        return;
      }

      notifyPreviewLayoutPartial(data.target, data.html);
    }).catch(function(error) {
      ui.error = error && error.message ? error.message : 'Failed to refresh layout preview.';
      render();
    });
  }

  function scheduleLayoutPreviewUpdate(settingId) {
    if (settingId) {
      var control = getLayoutControlById(settingId);
      if (control && control.preview_strategy === 'partial_render' && control.partial) {
        pendingLayoutPreviewPartials[control.partial] = true;
      }
    }

    if (ui.layoutPreviewTimer) {
      window.clearTimeout(ui.layoutPreviewTimer);
    }

    ui.layoutPreviewTimer = window.setTimeout(function() {
      var partials = Object.keys(pendingLayoutPreviewPartials);
      pendingLayoutPreviewPartials = {};
      ui.layoutPreviewTimer = null;
      notifyPreviewLayoutSettings();

      partials.forEach(refreshLayoutPartial);
    }, 150);
  }

  function notifyPreviewElementRemoved(zoneId, elementId) {
    var iframe = getPreviewIframe();
    if (!iframe || !iframe.contentWindow || !zoneId || !elementId) {
      return;
    }

    iframe.contentWindow.postMessage(
      {
        type: 'starterkit-builder-remove-element',
        zoneId: zoneId,
        elementId: elementId
      },
      getPreviewTargetOrigin()
    );
  }

  function refreshPreviewZone(zoneId, context) {
    requestZoneMarkup(zoneId, context || ui.context)
      .then(function(payload) {
        var iframe = getPreviewIframe();
        if (!iframe || !iframe.contentWindow || !payload || !payload.html) {
          return;
        }

        iframe.contentWindow.postMessage(
          {
            type: 'starterkit-builder-replace-zone',
            zoneId: payload.zoneId || zoneId,
            context: payload.context || context || ui.context,
            html: payload.html
          },
          getPreviewTargetOrigin()
        );
      })
      .catch(function(error) {
        ui.error = error && error.message ? error.message : 'Failed to refresh preview zone.';
        render();
      });
  }

  function getPreviewDragState() {
    if (!ui.drag) {
      return null;
    }

    return Object.assign({}, ui.drag, {
      allowedZones: getDroppableZoneIds()
    });
  }

  function getDroppableZoneIds() {
    var zoneIndex = getZoneIndex(ui.context);

    return Object.keys(zoneIndex).filter(function(zoneId) {
      return canDropOnZone(zoneId);
    });
  }

  function availableElements() {
    var available = [];

    if (!ui.selectedZone) {
      available = Object.keys(elements).filter(function(elementId) {
        return (elements[elementId].contexts || []).indexOf(ui.context) !== -1;
      });
    } else {
      var zone = getZoneSchema(ui.selectedZone, getSelectedContext());
      if (!zone) {
        return [];
      }

      available = zone.allowed_elements || [];
    }

    return available.filter(function(elementId) {
      if (!ui.search) {
        return true;
      }

      var definition = elements[elementId] || {};
      var haystack = [elementId, definition.label || ''].join(' ').toLowerCase();
      return haystack.indexOf(ui.search.toLowerCase()) !== -1;
    });
  }

  function moveSelected(offset) {
    var items = getZoneItems(ui.selectedZone, getSelectedContext());
    var index = items.findIndex(function(item) {
      return item.id === ui.selectedElementId;
    });

    if (index < 0) {
      return;
    }

    var target = index + offset;
    if (target < 0 || target >= items.length) {
      return;
    }

    var moved = items.splice(index, 1)[0];
    items.splice(target, 0, moved);
    markDirty();
    refreshPreviewZone(ui.selectedZone, getSelectedContext());
    render();
  }

  function moveSelectedToEdge(edge) {
    var items = getZoneItems(ui.selectedZone, getSelectedContext());
    var index = items.findIndex(function(item) {
      return item.id === ui.selectedElementId;
    });

    if (index < 0) {
      return;
    }

    var moved = items.splice(index, 1)[0];
    if (edge === 'top') {
      items.unshift(moved);
    } else {
      items.push(moved);
    }

    markDirty();
    refreshPreviewZone(ui.selectedZone, getSelectedContext());
    render();
  }

  function getZoneCapacity(zoneId, context) {
    var zone = getZoneSchema(zoneId, context);
    if (!zone || !zone.constraints) {
      return 999;
    }

    return Number(zone.constraints.max_items || 999);
  }

  function canAddElementToZone(elementId, zoneId, ignoreCurrentCount, excludeInstanceId, context) {
    context = context || ui.context;
    var zone = getZoneSchema(zoneId, context);
    if (!zone) {
      return false;
    }

    if ((zone.allowed_elements || []).indexOf(elementId) === -1) {
      return false;
    }

    var maxInstances = getElementMaxInstances(elementId);
    if (maxInstances > 0 && countElementInstances(elementId, excludeInstanceId, context) >= maxInstances) {
      return false;
    }

    var currentCount = getZoneItems(zoneId, context).length;
    var maxItems = getZoneCapacity(zoneId, context);
    if (ignoreCurrentCount) {
      return currentCount <= maxItems;
    }

    return currentCount < maxItems;
  }

  function findInstance(zoneId, instanceId) {
    var items = getZoneItems(zoneId);
    var index = items.findIndex(function(item) {
      return item.id === instanceId;
    });

    if (index < 0) {
      return null;
    }

    return {
      items: items,
      index: index,
      item: items[index]
    };
  }

  function clearDragState() {
    ui.drag = null;
    root.classList.remove('is-builder-dragging');
    root.classList.remove('is-builder-library-dragging');
    root.classList.remove('is-builder-instance-dragging');
    notifyPreviewDragState();
  }

  function startLibraryDrag(elementId) {
    ui.drag = {
      type: 'library',
      elementId: elementId
    };
    root.classList.add('is-builder-dragging');
    root.classList.add('is-builder-library-dragging');
    root.classList.remove('is-builder-instance-dragging');
    notifyPreviewDragState();
  }

  function startInstanceDrag(zoneId, instanceId) {
    var located = findInstance(zoneId, instanceId);
    if (!located) {
      return;
    }

    ui.drag = {
      type: 'instance',
      elementId: located.item.type,
      instanceId: instanceId,
      sourceZoneId: zoneId
    };
    root.classList.add('is-builder-dragging');
    root.classList.add('is-builder-instance-dragging');
    root.classList.remove('is-builder-library-dragging');
    notifyPreviewDragState();
  }

  function canDropOnZone(zoneId) {
    if (!ui.drag) {
      return false;
    }

    if (ui.drag.type === 'library') {
      return canAddElementToZone(ui.drag.elementId, zoneId, false);
    }

    if (ui.drag.type === 'instance') {
      if (ui.drag.sourceZoneId === zoneId) {
        return true;
      }

      return canAddElementToZone(ui.drag.elementId, zoneId, false, ui.drag.instanceId);
    }

    return false;
  }

  function clearPreviewDropState() {
    var iframe = root.querySelector('.starterkit-theme-builder__iframe');
    if (!iframe || !iframe.contentDocument) {
      return;
    }

    iframe.contentDocument
      .querySelectorAll('.starterkit-builder-zone.is-builder-droppable, .starterkit-builder-zone.is-builder-drop-invalid, .starterkit-builder-element.is-drop-before, .starterkit-builder-element.is-drop-after')
      .forEach(function(node) {
        node.classList.remove('is-builder-droppable', 'is-builder-drop-invalid', 'is-drop-before', 'is-drop-after');
      });
  }

  function getPreviewDropTarget(event) {
    var iframe = root.querySelector('.starterkit-theme-builder__iframe');
    if (!iframe || !iframe.contentDocument) {
      return null;
    }

    var rect = iframe.getBoundingClientRect();
    var x = event.clientX - rect.left;
    var y = event.clientY - rect.top;

    if (x < 0 || y < 0 || x > rect.width || y > rect.height) {
      return null;
    }

    var node = iframe.contentDocument.elementFromPoint(x, y);
    if (!node || !node.closest) {
      return null;
    }

    var zone = node.closest('[data-builder-zone]');
    if (!zone) {
      return null;
    }

    if ((zone.getAttribute('data-builder-zone-context') || 'master') !== ui.context) {
      return null;
    }

    var element = node.closest('[data-builder-element-id]');
    var targetElementId = '';
    var position = 'after';

    if (element && zone.contains(element)) {
      var elementRect = element.getBoundingClientRect();
      targetElementId = element.getAttribute('data-builder-element-id') || '';
      position = y < elementRect.top + elementRect.height / 2 ? 'before' : 'after';
    }

    return {
      zone: zone,
      zoneId: zone.getAttribute('data-builder-zone') || '',
      element: element,
      targetElementId: targetElementId,
      position: position
    };
  }

  function updatePreviewDropFeedback(event) {
    var target = getPreviewDropTarget(event);
    clearPreviewDropState();

    if (!target) {
      return null;
    }

    if (!canDropOnZone(target.zoneId)) {
      target.zone.classList.add('is-builder-drop-invalid');
      return target;
    }

    target.zone.classList.add('is-builder-droppable');

    if (target.element) {
      target.element.classList.add(target.position === 'before' ? 'is-drop-before' : 'is-drop-after');
    }

    return target;
  }

  function insertIntoZone(zoneId, item, targetElementId, position, context) {
    var items = getZoneItems(zoneId, context || ui.context);

    if (!targetElementId) {
      items.push(item);
      return;
    }

    var targetIndex = items.findIndex(function(existing) {
      return existing.id === targetElementId;
    });

    if (targetIndex < 0) {
      items.push(item);
      return;
    }

    var insertIndex = position === 'before' ? targetIndex : targetIndex + 1;
    items.splice(insertIndex, 0, item);
  }

  function addLibraryElementToSelectedZone(elementId) {
    var zoneId = ui.selectedZone;
    var context = getSelectedContext();

    if (!zoneId || !getZoneSchema(zoneId, context)) {
      ui.error = 'Select a zone before adding an element.';
      render();
      return;
    }

    if (!canAddElementToZone(elementId, zoneId, false, '', context)) {
      ui.error = getElementLimitMessage(elementId);
      render();
      return;
    }

    var instance = makeInstance(elementId);
    insertIntoZone(zoneId, instance, '', 'after', context);

    ui.selectedContext = context;
    ui.selectedZone = zoneId;
    ui.selectedElementId = instance.id;
    ui.error = '';
    markDirty();
    refreshPreviewZone(zoneId, context);
    render();
  }

  function dropOnZone(zoneId, targetElementId, position) {
    if (!ui.drag || !canDropOnZone(zoneId)) {
      clearDragState();
      render();
      return;
    }

    if (ui.drag.type === 'library') {
      var instance = makeInstance(ui.drag.elementId);
      insertIntoZone(zoneId, instance, targetElementId, position, ui.context);
      ui.selectedZone = zoneId;
      ui.selectedElementId = instance.id;
      clearDragState();
      markDirty();
      refreshPreviewZone(zoneId, ui.context);
      render();
      return;
    }

    if (ui.drag.type === 'instance') {
      var located = findInstance(ui.drag.sourceZoneId, ui.drag.instanceId);
      if (!located) {
        clearDragState();
        render();
        return;
      }

      if (ui.drag.sourceZoneId === zoneId && targetElementId === ui.drag.instanceId) {
        clearDragState();
        render();
        return;
      }

      var moved = located.items.splice(located.index, 1)[0];
      var sourceZoneId = ui.drag.sourceZoneId;
      insertIntoZone(zoneId, moved, targetElementId, position, ui.context);
      ui.selectedZone = zoneId;
      ui.selectedElementId = moved.id;
      clearDragState();
      markDirty();
      refreshPreviewZone(zoneId, ui.context);
      if (sourceZoneId && sourceZoneId !== zoneId) {
        refreshPreviewZone(sourceZoneId, ui.context);
      }
      render();
    }
  }

  function deleteSelected() {
    var zoneId = ui.selectedZone;
    var context = getSelectedContext();
    var elementId = ui.selectedElementId;
    var items = getZoneItems(ui.selectedZone, getSelectedContext());
    var index = items.findIndex(function(item) {
      return item.id === ui.selectedElementId;
    });

    if (index < 0) {
      return;
    }

    items.splice(index, 1);
    ui.selectedElementId = '';
    markDirty();
    refreshPreviewZone(zoneId, context);
    render();
  }

  function toggleSelected() {
    var item = getSelectedElement();
    if (!item) {
      return;
    }

    item.enabled = !item.enabled;
    markDirty();
    refreshPreviewZone(ui.selectedZone, getSelectedContext());
    render();
  }

  function buildLibraryHtml() {
    var items = availableElements()
      .map(function(elementId) {
        var definition = elements[elementId];
        var maxInstances = getElementMaxInstances(elementId);
        var isLimited = maxInstances > 0 && countElementInstances(elementId) >= maxInstances;
        if (!definition) {
          return '';
        }

        return (
          '<button type="button" class="starterkit-theme-builder__library-item' + (isLimited ? ' is-disabled' : '') + '" ' + (isLimited ? 'disabled ' : 'draggable="true" data-drag-type="library" data-action="add-library-element" ') + 'data-element-id="' +
          escapeHtml(elementId) +
          '">' +
          '<span class="starterkit-theme-builder__library-icon" aria-hidden="true">' +
          escapeHtml(getElementIconLabel(elementId)) +
          '</span>' +
          '<strong class="starterkit-theme-builder__library-label">' +
          escapeHtml(definition.label) +
          '</strong>' +
          (isLimited ? '<span class="starterkit-theme-builder__library-status">Limit reached</span>' : '') +
          '</button>'
        );
      })
      .join('');

    return items || '<p class="starterkit-theme-builder__empty">No matching elements for this context or zone.</p>';
  }

  function buildZoneListHtml() {
    var zoneIndex = getZoneIndex(ui.context);

    var options = Object.keys(zoneIndex)
      .map(function(zoneId) {
        var zone = zoneIndex[zoneId];
        var count = getZoneItems(zoneId).length;

        return (
          '<option value="' +
          escapeHtml(zoneId) +
          '"' +
          (zoneId === ui.selectedZone ? ' selected' : '') +
          '>' +
          escapeHtml(zone.label + ' - ' + zone.presetId + ' - ' + count + ' items') +
          '</option>'
        );
      })
      .join('');

    return (
      '<label class="starterkit-theme-builder__control starterkit-theme-builder__zone-select-control">' +
      '<span>Zone</span>' +
      '<select data-action="select-zone-dropdown">' +
      '<option value="">Select a zone</option>' +
      options +
      '</select>' +
      '</label>'
    );
  }

  function buildNavbarHtml() {
    return (
      '<div class="starterkit-theme-builder__navbar">' +
      '<div class="starterkit-theme-builder__navbar-left">' +
      '<div class="starterkit-theme-builder__brand">' +
      '<span class="starterkit-theme-builder__brand-mark">TB</span>' +
      '<strong>Theme Builder</strong>' +
      '</div>' +
      '</div>' +
      '<nav class="starterkit-theme-builder__nav" aria-label="Builder layout context">' +
      contexts.map(function(context) {
        return (
          '<button type="button" class="starterkit-theme-builder__nav-item' +
          (context.id === ui.context ? ' is-active' : '') +
          '" data-action="change-context-button" data-context="' +
          escapeHtml(context.id) +
          '">' +
          escapeHtml(getContextLabel(context.id)) +
          '</button>'
        );
      }).join('') +
      '</nav>' +
      '<div class="starterkit-theme-builder__navbar-actions">' +
      (ui.context === 'master' ? '<button class="button' + (ui.inspectorMode === 'settings' ? ' button-primary' : '') + '" data-action="open-layout-settings">Settings</button>' : '') +
      '<button class="button button-primary" data-action="save-publish"' + (ui.loading || !ui.dirty || ui.hasConflict ? ' disabled' : '') + '>' + (ui.loading ? 'Publishing...' : 'Save &amp; Publish') + '</button>' +
      (ui.hasConflict ? '<button class="button" data-action="reload-latest">Reload Latest State</button>' : '') +
      '<button type="button" class="button starterkit-theme-builder__exit-button" data-action="exit-builder" aria-label="Exit Theme Builder">Exit</button>' +
      '</div>' +
      '</div>'
    );
  }

  function buildHelpHtml(control) {
    return control.help ? '<span class="starterkit-theme-builder__control-help">' + escapeHtml(control.help) + '</span>' : '';
  }

  function buildCommonInputAttributes(control) {
    var attrs = '';

    if (control.placeholder) {
      attrs += ' placeholder="' + escapeHtml(control.placeholder) + '"';
    }

    ['min', 'max', 'step', 'rows', 'accept'].forEach(function(key) {
      if (control[key] !== undefined && control[key] !== '') {
        attrs += ' ' + key + '="' + escapeHtml(control[key]) + '"';
      }
    });

    return attrs;
  }

  function getControlById(definition, controlId) {
    return (definition.settings_schema || []).find(function(control) {
      return control.id === controlId;
    }) || null;
  }

  function getInputValue(input) {
    if (input.type === 'checkbox') {
      return input.checked ? '1' : '0';
    }

    return input.value;
  }

  function updateSelectedElementSetting(settingId, value, options) {
    var element = getSelectedElement();
    if (!element) {
      return;
    }

    options = options || {};
    if (String(element.settings[settingId] === undefined ? '' : element.settings[settingId]) === String(value)) {
      return;
    }

    element.settings[settingId] = value;
    ui.error = '';
    markDirty('builder');
    refreshPreviewZone(ui.selectedZone, getSelectedContext());

    if (options.render !== false) {
      render();
    }
  }

  function getRepeaterRowDefaults(control) {
    var row = {};

    (control.fields || []).forEach(function(field) {
      row[field.id] = field.default !== undefined ? cloneValue(field.default) : '';
    });

    return row;
  }

  function updateRepeaterField(settingId, rowIndex, fieldId, value, options) {
    var element = getSelectedElement();
    if (!element) {
      return;
    }

    options = options || {};
    var existingRow = Array.isArray(element.settings[settingId]) ? element.settings[settingId][rowIndex] : null;
    if (existingRow && String(existingRow[fieldId] === undefined ? '' : existingRow[fieldId]) === String(value)) {
      return;
    }

    if (!Array.isArray(element.settings[settingId])) {
      element.settings[settingId] = [];
    }

    if (!element.settings[settingId][rowIndex]) {
      element.settings[settingId][rowIndex] = {};
    }

    element.settings[settingId][rowIndex][fieldId] = value;
    ui.error = '';
    markDirty('builder');
    refreshPreviewZone(ui.selectedZone, getSelectedContext());

    if (options.render !== false) {
      render();
    }
  }

  function buildColorInputHtml(label, value, dataAttrs, commonAttrs, helpHtml) {
    return (
      '<label class="starterkit-theme-builder__control starterkit-theme-builder__control--color">' +
      '<span>' + escapeHtml(label) + '</span>' +
      '<input type="text" class="starterkit-theme-builder__color-input" value="' + escapeHtml(value) + '"' + dataAttrs + commonAttrs + ' data-coloris>' +
      helpHtml +
      '</label>'
    );
  }

  function buildRepeaterFieldHtml(control, row, rowIndex, field) {
    var value = row && row[field.id] !== undefined ? row[field.id] : (field.default !== undefined ? field.default : '');
    var commonAttrs = buildCommonInputAttributes(field);
    var dataAttrs = ' data-setting-id="' + escapeHtml(control.id) + '" data-repeater-index="' + rowIndex + '" data-repeater-field-id="' + escapeHtml(field.id) + '"';

    if (field.type === 'textarea') {
      return (
        '<label class="starterkit-theme-builder__control">' +
        '<span>' + escapeHtml(field.label) + '</span>' +
        '<textarea' + dataAttrs + commonAttrs + '>' + escapeHtml(value) + '</textarea>' +
        buildHelpHtml(field) +
        '</label>'
      );
    }

    if (field.type === 'select') {
      return (
        '<label class="starterkit-theme-builder__control">' +
        '<span>' + escapeHtml(field.label) + '</span>' +
        '<select' + dataAttrs + '>' +
        (field.options || []).map(function(option) {
          var selected = option.value === value ? ' selected' : '';
          return '<option value="' + escapeHtml(option.value) + '"' + selected + '>' + escapeHtml(option.label) + '</option>';
        }).join('') +
        '</select>' +
        buildHelpHtml(field) +
        '</label>'
      );
    }

    if (field.type === 'toggle' || field.type === 'checkbox') {
      return (
        '<label class="starterkit-theme-builder__control starterkit-theme-builder__control--toggle">' +
        '<input type="checkbox"' + dataAttrs + (value === true || value === '1' ? ' checked' : '') + '>' +
        '<span>' + escapeHtml(field.label) + '</span>' +
        buildHelpHtml(field) +
        '</label>'
      );
    }

    if (field.type === 'color') {
      return buildColorInputHtml(field.label, value, dataAttrs, commonAttrs, buildHelpHtml(field));
    }

    var inputType = field.type === 'url' ? 'url' : (field.type === 'number' || field.type === 'range' ? field.type : 'text');

    return (
      '<label class="starterkit-theme-builder__control">' +
      '<span>' + escapeHtml(field.label) + '</span>' +
      '<input type="' + escapeHtml(inputType) + '" value="' + escapeHtml(value) + '"' + dataAttrs + commonAttrs + '>' +
      buildHelpHtml(field) +
      '</label>'
    );
  }

  function buildControlHtml(control, value) {
    var commonAttrs = buildCommonInputAttributes(control);

    if (control.type === 'textarea') {
      return (
        '<label class="starterkit-theme-builder__control">' +
        '<span>' + escapeHtml(control.label) + '</span>' +
        '<textarea data-setting-id="' + escapeHtml(control.id) + '"' + commonAttrs + '>' + escapeHtml(value) + '</textarea>' +
        buildHelpHtml(control) +
        '</label>'
      );
    }

    if (control.type === 'select') {
      return (
        '<label class="starterkit-theme-builder__control">' +
        '<span>' + escapeHtml(control.label) + '</span>' +
        '<select data-setting-id="' + escapeHtml(control.id) + '">' +
        (control.options || []).map(function(option) {
          var selected = option.value === value ? ' selected' : '';
          return '<option value="' + escapeHtml(option.value) + '"' + selected + '>' + escapeHtml(option.label) + '</option>';
        }).join('') +
        '</select>' +
        buildHelpHtml(control) +
        '</label>'
      );
    }

    if (control.type === 'toggle' || control.type === 'checkbox') {
      return (
        '<label class="starterkit-theme-builder__control starterkit-theme-builder__control--toggle">' +
        '<input type="checkbox" data-setting-id="' + escapeHtml(control.id) + '"' + (value === true || value === '1' ? ' checked' : '') + '>' +
        '<span>' + escapeHtml(control.label) + '</span>' +
        buildHelpHtml(control) +
        '</label>'
      );
    }

    if (control.type === 'range') {
      return (
        '<label class="starterkit-theme-builder__control">' +
        '<span>' + escapeHtml(control.label) + '</span>' +
        '<div class="starterkit-theme-builder__range-control">' +
        '<input type="range" value="' + escapeHtml(value) + '" data-setting-id="' + escapeHtml(control.id) + '"' + commonAttrs + '>' +
        '<input type="number" value="' + escapeHtml(value) + '" data-setting-id="' + escapeHtml(control.id) + '"' + commonAttrs + '>' +
        '</div>' +
        buildHelpHtml(control) +
        '</label>'
      );
    }

    if (control.type === 'color') {
      return buildColorInputHtml(
        control.label,
        value,
        ' data-setting-id="' + escapeHtml(control.id) + '"',
        commonAttrs,
        buildHelpHtml(control)
      );
    }

    if (control.type === 'image') {
      return (
        '<div class="starterkit-theme-builder__control">' +
        '<span>' + escapeHtml(control.label) + '</span>' +
        '<input type="hidden" value="' + escapeHtml(value) + '" data-setting-id="' + escapeHtml(control.id) + '">' +
        '<div class="starterkit-theme-builder__image-control">' +
        '<span>' + (value ? 'Attachment #' + escapeHtml(value) : 'No image selected') + '</span>' +
        '<button type="button" class="button" data-action="choose-image" data-setting-id="' + escapeHtml(control.id) + '">Choose</button>' +
        '<button type="button" class="button" data-action="clear-image" data-setting-id="' + escapeHtml(control.id) + '"' + (value ? '' : ' disabled') + '>Clear</button>' +
        '</div>' +
        buildHelpHtml(control) +
        '</div>'
      );
    }

    if (control.type === 'repeater') {
      var rows = Array.isArray(value) ? value : [];
      var rowLabel = control.item_label || 'Item';

      return (
        '<div class="starterkit-theme-builder__control starterkit-theme-builder__repeater">' +
        '<span>' + escapeHtml(control.label) + '</span>' +
        rows.map(function(row, rowIndex) {
          return (
            '<div class="starterkit-theme-builder__repeater-row">' +
            '<div class="starterkit-theme-builder__repeater-row-header">' +
            '<strong>' + escapeHtml(rowLabel) + ' ' + (rowIndex + 1) + '</strong>' +
            '<button type="button" class="button" data-action="remove-repeater-row" data-setting-id="' + escapeHtml(control.id) + '" data-repeater-index="' + rowIndex + '">Remove</button>' +
            '</div>' +
            (control.fields || []).map(function(field) {
              return buildRepeaterFieldHtml(control, row, rowIndex, field);
            }).join('') +
            '</div>'
          );
        }).join('') +
        '<button type="button" class="button" data-action="add-repeater-row" data-setting-id="' + escapeHtml(control.id) + '">Add ' + escapeHtml(rowLabel) + '</button>' +
        buildHelpHtml(control) +
        '</div>'
      );
    }

    var inputType = control.type === 'url' ? 'url' : (control.type === 'number' || control.type === 'datetime-local' ? control.type : 'text');

    return (
      '<label class="starterkit-theme-builder__control">' +
      '<span>' + escapeHtml(control.label) + '</span>' +
      '<input type="' + escapeHtml(inputType) + '" value="' + escapeHtml(value) + '" data-setting-id="' + escapeHtml(control.id) + '"' + commonAttrs + '>' +
      buildHelpHtml(control) +
      '</label>'
    );
  }

  function buildLayoutControlHtml(control, value) {
    var commonAttrs = buildCommonInputAttributes(control);
    var dataAttr = ' data-layout-setting-id="' + escapeHtml(control.id) + '"';

    if (control.type === 'select') {
      return (
        '<label class="starterkit-theme-builder__control">' +
        '<span>' + escapeHtml(control.label) + '</span>' +
        '<select' + dataAttr + '>' +
        (control.options || []).map(function(option) {
          var selected = String(option.value) === String(value) ? ' selected' : '';
          return '<option value="' + escapeHtml(option.value) + '"' + selected + '>' + escapeHtml(option.label) + '</option>';
        }).join('') +
        '</select>' +
        buildHelpHtml(control) +
        '</label>'
      );
    }

    if (control.type === 'toggle' || control.type === 'checkbox') {
      return (
        '<label class="starterkit-theme-builder__control starterkit-theme-builder__control--toggle">' +
        '<input type="checkbox"' + dataAttr + (value === true || value === '1' ? ' checked' : '') + '>' +
        '<span>' + escapeHtml(control.label) + '</span>' +
        buildHelpHtml(control) +
        '</label>'
      );
    }

    if (control.type === 'range') {
      return (
        '<label class="starterkit-theme-builder__control">' +
        '<span>' + escapeHtml(control.label) + '</span>' +
        '<div class="starterkit-theme-builder__range-control">' +
        '<input type="range" value="' + escapeHtml(value) + '"' + dataAttr + commonAttrs + '>' +
        '<input type="number" value="' + escapeHtml(value) + '"' + dataAttr + commonAttrs + '>' +
        '</div>' +
        buildHelpHtml(control) +
        '</label>'
      );
    }

    if (control.type === 'color') {
      return buildColorInputHtml(
        control.label,
        value,
        dataAttr,
        commonAttrs,
        buildHelpHtml(control)
      );
    }

    var inputType = control.type === 'url' ? 'url' : (control.type === 'number' ? 'number' : 'text');

    return (
      '<label class="starterkit-theme-builder__control">' +
      '<span>' + escapeHtml(control.label) + '</span>' +
      '<input type="' + escapeHtml(inputType) + '" value="' + escapeHtml(value) + '"' + dataAttr + commonAttrs + '>' +
      buildHelpHtml(control) +
      '</label>'
    );
  }

  function getLayoutControlById(settingId) {
    var found = null;

    Object.keys(layoutSettingsSchemas || {}).some(function(layoutId) {
      var schema = layoutSettingsSchemas[layoutId] || {};
      return (schema.settings_schema || []).some(function(control) {
        if (control.id !== settingId) {
          return false;
        }

        found = control;
        return true;
      });
    });

    return found;
  }

  function updateLayoutSetting(settingId, value, options) {
    if (!settingId) {
      return;
    }

    options = options || {};
    if (String(layoutSettings[settingId] === undefined ? '' : layoutSettings[settingId]) === String(value)) {
      return;
    }

    layoutSettings[settingId] = value;
    ui.error = '';
    markDirty('layout');
    scheduleLayoutPreviewUpdate(settingId);

    if (options.render !== false) {
      render();
    } else {
      syncPublishControls();
    }
  }

  function buildLayoutSettingsHtml() {
    var sections = Object.keys(layoutSettingsSchemas || {}).map(function(layoutId) {
      var schema = layoutSettingsSchemas[layoutId] || {};
      var controls = schema.settings_schema || [];

      if (!controls.length) {
        return '';
      }

      return (
        '<div class="starterkit-theme-builder__panel-section starterkit-theme-builder__layout-settings-section">' +
        '<h3>' + escapeHtml(schema.label || layoutId) + '</h3>' +
        '<p class="starterkit-theme-builder__meta">Layout settings</p>' +
        controls.map(function(control) {
          var value = layoutSettings[control.id];
          if (value === undefined) {
            value = control.default !== undefined ? cloneValue(control.default) : '';
          }

          return buildLayoutControlHtml(control, value);
        }).join('') +
        '</div>'
      );
    }).join('');

    return sections || '<p class="starterkit-theme-builder__empty">No configurable settings for the active master layouts.</p>';
  }

  function initColorPicker() {
    if (!window.Coloris) {
      return;
    }

    if (colorPickerInitialized) {
      if (typeof window.Coloris.wrap === 'function') {
        window.Coloris.wrap('.starterkit-theme-builder__color-input');
      }

      return;
    }

    window.Coloris({
      el: '.starterkit-theme-builder__color-input',
      theme: 'polaroid',
      themeMode: 'light',
      format: 'hex',
      alpha: false,
      clearButton: true,
      swatches: [
        '#111827',
        '#334155',
        '#f59e0b',
        '#2563eb',
        '#16a34a',
        '#dc2626',
        '#ffffff',
        '#000000'
      ]
    });

    colorPickerInitialized = true;
  }

  function chooseImage(settingId) {
    if (!window.wp || !window.wp.media) {
      ui.error = 'Media library is unavailable on this screen.';
      render();
      return;
    }

    var frame = window.wp.media({
      title: 'Choose Image',
      button: {
        text: 'Use Image'
      },
      multiple: false
    });

    frame.on('select', function() {
      var selected = frame.state().get('selection').first();
      var attachment = selected ? selected.toJSON() : null;

      if (attachment && attachment.id) {
        updateSelectedElementSetting(settingId, String(attachment.id));
      }
    });

    frame.open();
  }

  function buildInspectorHtml() {
    if (ui.context === 'master' && ui.inspectorMode === 'settings') {
      return buildLayoutSettingsHtml();
    }

    var zone = getZoneSchema(ui.selectedZone, getSelectedContext());
    var element = getSelectedElement();

    if (!zone) {
      return '<p class="starterkit-theme-builder__empty">Select an element that has already been added to a preview zone.</p>';
    }

    if (!element) {
      return '<p class="starterkit-theme-builder__empty">Zone selected: ' + escapeHtml(zone.label) + '. Drop an element into this zone, then click the added element to edit its settings.</p>';
    }

    var definition = elements[element.type];
    var actionsHtml =
      '<div class="starterkit-theme-builder__inspector-actions">' +
      '<button type="button" class="button" data-action="move-up">Move Up</button>' +
      '<button type="button" class="button" data-action="move-down">Move Down</button>' +
      '<button type="button" class="button" data-action="toggle">' + (element.enabled ? 'Disable' : 'Enable') + '</button>' +
      '<button type="button" class="button starterkit-theme-builder__danger-button" data-action="delete">Delete</button>' +
      '</div>';
    var controlsHtml = (definition.settings_schema || []).map(function(control) {
      var value = element.settings[control.id];
      if (value === undefined) {
        value = control.default !== undefined ? cloneValue(control.default) : '';
      }

      return buildControlHtml(control, value);
    }).join('');

    return (
      '<div class="starterkit-theme-builder__panel-section">' +
      '<h3>' + escapeHtml(definition.label) + '</h3>' +
      '<p class="starterkit-theme-builder__meta">' + escapeHtml(zone.label) + ' • ' + (element.enabled ? 'Enabled' : 'Disabled') + '</p>' +
      actionsHtml +
      controlsHtml +
      '</div>'
    );
  }

  function render() {
    ensureShell();
    root.setAttribute('data-device-mode', ui.deviceMode);

    root.querySelector('.js-builder-navbar').innerHTML = buildNavbarHtml();

    root.querySelector('.js-builder-sidebar').innerHTML =
      '<div class="starterkit-theme-builder__sidebar">' +
      '<div class="starterkit-theme-builder__panel-section starterkit-theme-builder__panel-section--zones">' +
      '<h3>Zones</h3>' +
      '<div class="starterkit-theme-builder__zone-list">' + buildZoneListHtml() + '</div>' +
      '</div>' +
      '<div class="starterkit-theme-builder__panel-section starterkit-theme-builder__panel-section--library">' +
      '<h3>Element Library</h3>' +
      '<p class="starterkit-theme-builder__meta">Select a zone, then click or drag an element into the preview.</p>' +
      '<label class="starterkit-theme-builder__control">' +
      '<span>Search Elements</span>' +
      '<input type="search" value="' + escapeHtml(ui.search) + '" data-action="search-elements" placeholder="Search by name or id">' +
      '</label>' +
      '<div class="starterkit-theme-builder__stack">' + buildLibraryHtml() + '</div>' +
      '</div>' +
      '</div>';

    root.querySelector('.js-builder-preview-toolbar').innerHTML =
      '<div class="starterkit-theme-builder__preview-status">' +
      '<strong>Preview</strong>' +
      '<span>' + (ui.loading ? 'Publishing...' : (ui.hasConflict ? 'Conflict detected' : (ui.dirty ? 'Unsaved changes' : 'Published'))) + '</span>' +
      '</div>' +
      '<div class="starterkit-theme-builder__preview-actions">' +
      ['desktop', 'tablet', 'mobile'].map(function(mode) {
        return '<button class="button' + (mode === ui.deviceMode ? ' button-primary' : '') + '" data-action="change-device" data-device="' + mode + '">' + mode + '</button>';
      }).join('') +
      '<button class="button" data-action="refresh-preview">Refresh Preview</button>' +
      '</div>' +
      (ui.error ? '<span class="starterkit-theme-builder__error">' + escapeHtml(ui.error) + '</span>' : '');

    root.querySelector('.js-builder-inspector').innerHTML =
      '<div class="starterkit-theme-builder__inspector">' +
      '<div class="starterkit-theme-builder__panel-section">' +
      '<h3>Inspector</h3>' +
      buildInspectorHtml() +
      '</div>' +
      '</div>';

    notifyPreviewSelection();
    notifyPreviewDragState();
    notifyPreviewLayoutSettings();
    initColorPicker();
  }

  root.addEventListener('input', function(event) {
    if (event.target.matches('[data-layout-setting-id]')) {
      updateLayoutSetting(event.target.getAttribute('data-layout-setting-id') || '', getInputValue(event.target), { render: false });
      return;
    }

    if (!event.target.matches('.starterkit-theme-builder__color-input[data-setting-id]')) {
      return;
    }

    var settingId = event.target.getAttribute('data-setting-id');
    var repeaterFieldId = event.target.getAttribute('data-repeater-field-id');

    if (repeaterFieldId) {
      updateRepeaterField(
        settingId,
        Number(event.target.getAttribute('data-repeater-index') || 0),
        repeaterFieldId,
        event.target.value,
        { render: false }
      );
      return;
    }

    updateSelectedElementSetting(settingId, event.target.value, { render: false });
  });

  root.addEventListener('change', function(event) {
    if (event.target.matches('[data-action="change-context"]')) {
      ui.context = event.target.value;
      ui.inspectorMode = ui.context === 'master' ? 'settings' : 'element';
      ui.selectedContext = '';
      ui.selectedZone = '';
      ui.selectedElementId = '';
      reloadPreview();
      render();
      return;
    }

    if (event.target.matches('[data-action="search-elements"]')) {
      ui.search = event.target.value || '';
      render();
      return;
    }

    if (event.target.matches('[data-action="select-zone-dropdown"]')) {
      selectZone(event.target.value || '');
      return;
    }

    if (event.target.matches('[data-setting-id]')) {
      var settingId = event.target.getAttribute('data-setting-id');
      var repeaterFieldId = event.target.getAttribute('data-repeater-field-id');

      if (repeaterFieldId) {
        updateRepeaterField(
          settingId,
          Number(event.target.getAttribute('data-repeater-index') || 0),
          repeaterFieldId,
          getInputValue(event.target)
        );
        return;
      }

      if (event.target.type === 'hidden') {
        return;
      }

      updateSelectedElementSetting(settingId, getInputValue(event.target));
    }

    if (event.target.matches('[data-layout-setting-id]')) {
      updateLayoutSetting(event.target.getAttribute('data-layout-setting-id') || '', getInputValue(event.target));
    }
  });

  root.addEventListener('click', function(event) {
    var button = event.target.closest('[data-action]');
    if (!button) {
      return;
    }

    var action = button.getAttribute('data-action');

    if (action === 'change-context-button') {
      ui.context = button.getAttribute('data-context') || ui.context;
      ui.inspectorMode = ui.context === 'master' ? 'settings' : 'element';
      ui.selectedContext = '';
      ui.selectedZone = '';
      ui.selectedElementId = '';
      reloadPreview();
      render();
      return;
    }

    if (action === 'open-layout-settings') {
      ui.inspectorMode = 'settings';
      ui.selectedContext = '';
      ui.selectedZone = '';
      ui.selectedElementId = '';
      render();
      notifyPreviewSelection();
      notifyPreviewLayoutSettings();
      return;
    }

    if (action === 'exit-builder') {
      if (ui.dirty && !window.confirm('You have unpublished changes. Exit Theme Builder?')) {
        return;
      }

      window.location.href = exitUrl || 'admin.php?page=starterkit-theme-builder';
      return;
    }

    if (action === 'change-device') {
      ui.deviceMode = button.getAttribute('data-device');
      reloadPreview();
      render();
      return;
    }

    if (action === 'select-zone') {
      selectZone(button.getAttribute('data-zone-id'));
      return;
    }

    if (action === 'select-element') {
      selectElement(button.getAttribute('data-zone-id'), button.getAttribute('data-element-instance-id'));
      return;
    }

    if (action === 'add-library-element') {
      addLibraryElementToSelectedZone(button.getAttribute('data-element-id') || '');
      return;
    }

    if (action === 'choose-image') {
      chooseImage(button.getAttribute('data-setting-id') || '');
      return;
    }

    if (action === 'clear-image') {
      updateSelectedElementSetting(button.getAttribute('data-setting-id') || '', '');
      return;
    }

    if (action === 'add-repeater-row') {
      var addElement = getSelectedElement();
      var addDefinition = addElement ? elements[addElement.type] : null;
      var addSettingId = button.getAttribute('data-setting-id') || '';
      var addControl = addDefinition ? getControlById(addDefinition, addSettingId) : null;

      if (!addElement || !addControl) {
        return;
      }

      if (!Array.isArray(addElement.settings[addSettingId])) {
        addElement.settings[addSettingId] = [];
      }

      addElement.settings[addSettingId].push(getRepeaterRowDefaults(addControl));
      markDirty();
      refreshPreviewZone(ui.selectedZone, getSelectedContext());
      render();
      return;
    }

    if (action === 'remove-repeater-row') {
      var removeElement = getSelectedElement();
      var removeSettingId = button.getAttribute('data-setting-id') || '';
      var removeIndex = Number(button.getAttribute('data-repeater-index') || 0);

      if (!removeElement || !Array.isArray(removeElement.settings[removeSettingId])) {
        return;
      }

      removeElement.settings[removeSettingId].splice(removeIndex, 1);
      markDirty();
      refreshPreviewZone(ui.selectedZone, getSelectedContext());
      render();
      return;
    }

    if (action === 'move-up') {
      moveSelected(-1);
      return;
    }

    if (action === 'move-top') {
      moveSelectedToEdge('top');
      return;
    }

    if (action === 'move-down') {
      moveSelected(1);
      return;
    }

    if (action === 'move-bottom') {
      moveSelectedToEdge('bottom');
      return;
    }

    if (action === 'toggle') {
      toggleSelected();
      return;
    }

    if (action === 'reload-latest') {
      reloadLatestState();
      return;
    }

    if (action === 'save-publish') {
      saveState();
      return;
    }

    if (action === 'refresh-preview') {
      reloadPreview(true);
      return;
    }

    if (action === 'delete') {
      if (!window.confirm('Delete this element instance?')) {
        return;
      }
      deleteSelected();
    }
  });

  root.addEventListener('dragstart', function(event) {
    var libraryItem = event.target.closest('[data-drag-type="library"]');
    var instanceItem = event.target.closest('[data-drag-type="instance"]');

    if (libraryItem) {
      var libraryElementId = libraryItem.getAttribute('data-element-id');
      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'copy';
        event.dataTransfer.setData('text/plain', libraryElementId);
      }
      startLibraryDrag(libraryElementId);
      return;
    }

    if (instanceItem) {
      var instanceId = instanceItem.getAttribute('data-drag-instance-id');
      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', instanceId);
      }
      startInstanceDrag(instanceItem.getAttribute('data-drag-zone-id'), instanceId);
    }
  });

  root.addEventListener('dragend', function() {
    clearDragState();
    render();
  });

  root.addEventListener('dragover', function(event) {
    var dropTarget = event.target.closest('[data-drop-zone-id]');
    if (!dropTarget) {
      return;
    }

    if (!canDropOnZone(dropTarget.getAttribute('data-drop-zone-id'))) {
      return;
    }

    event.preventDefault();

    root
      .querySelectorAll('.starterkit-theme-builder__element-item.is-drop-before, .starterkit-theme-builder__element-item.is-drop-after')
      .forEach(function(node) {
        node.classList.remove('is-drop-before', 'is-drop-after');
      });

    if (dropTarget.matches('.starterkit-theme-builder__element-item')) {
      var rect = dropTarget.getBoundingClientRect();
      var position = event.clientY < rect.top + rect.height / 2 ? 'before' : 'after';
      dropTarget.classList.add(position === 'before' ? 'is-drop-before' : 'is-drop-after');
      dropTarget.setAttribute('data-drop-position', position);
    }
  });

  root.addEventListener('drop', function(event) {
    var dropTarget = event.target.closest('[data-drop-zone-id]');
    if (!dropTarget) {
      return;
    }

    event.preventDefault();
    dropOnZone(
      dropTarget.getAttribute('data-drop-zone-id'),
      dropTarget.getAttribute('data-drop-target-element-id') || '',
      dropTarget.getAttribute('data-drop-position') || 'after'
    );
    root
      .querySelectorAll('.starterkit-theme-builder__element-item.is-drop-before, .starterkit-theme-builder__element-item.is-drop-after')
      .forEach(function(node) {
        node.classList.remove('is-drop-before', 'is-drop-after');
      });
  });

  root.addEventListener('dragover', function(event) {
    if (!ui.drag || !event.target.closest('[data-preview-drop-layer]')) {
      return;
    }

    var target = updatePreviewDropFeedback(event);

    if (!target || !canDropOnZone(target.zoneId)) {
      return;
    }

    event.preventDefault();
    if (event.dataTransfer) {
      event.dataTransfer.dropEffect = ui.drag.type === 'library' ? 'copy' : 'move';
    }
  });

  root.addEventListener('dragleave', function(event) {
    if (!event.target.closest('[data-preview-drop-layer]')) {
      return;
    }

    clearPreviewDropState();
  });

  root.addEventListener('drop', function(event) {
    if (!ui.drag || !event.target.closest('[data-preview-drop-layer]')) {
      return;
    }

    var target = updatePreviewDropFeedback(event);

    if (!target || !canDropOnZone(target.zoneId)) {
      clearPreviewDropState();
      clearDragState();
      render();
      return;
    }

    event.preventDefault();
    dropOnZone(target.zoneId, target.targetElementId, target.position);
    clearPreviewDropState();
  });

  window.addEventListener('message', function(event) {
    if (!event.data || !isPreviewMessage(event)) {
      return;
    }

    if (event.data.type === 'starterkit-builder-select') {
      if (event.data.elementId) {
        selectElement(event.data.zoneId, event.data.elementId, event.data.context || '');
      } else {
        selectZone(event.data.zoneId, event.data.context || '');
      }
    }

    if (event.data.type === 'starterkit-builder-ready') {
      notifyPreviewSelection();
      notifyPreviewDragState();
      notifyPreviewLayoutSettings();
    }

    if (event.data.type === 'starterkit-builder-drop') {
      dropOnZone(event.data.zoneId || '', event.data.targetElementId || '', event.data.position || 'after');
      return;
    }

    if (event.data.type === 'starterkit-builder-start-instance-drag') {
      if ((event.data.context || ui.context) !== ui.context) {
        return;
      }

      startInstanceDrag(event.data.zoneId || '', event.data.elementId || '');
      return;
    }

    if (event.data.type === 'starterkit-builder-end-drag') {
      clearPreviewDropState();
      clearDragState();
      render();
      return;
    }

    if (event.data.type === 'starterkit-builder-delete-element') {
      selectElement(event.data.zoneId || '', event.data.elementId || '', event.data.context || '');
      deleteSelected();
      return;
    }

    if (event.data.type === 'starterkit-builder-hover-zone') {
      return;
    }
  });

  window.addEventListener('beforeunload', function(event) {
    if (!ui.dirty && !ui.loading) {
      return;
    }

    event.preventDefault();
    event.returnValue = '';
  });

  render();
})();
