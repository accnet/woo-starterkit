document.addEventListener('DOMContentLoaded', function () {
  var typeSelect = document.getElementById('starterkit_section_type');
  var slotSelect = document.getElementById('starterkit_section_slot');
  var configNode = document.getElementById('starterkit-section-types-data');

  if (!typeSelect || !slotSelect || !configNode) {
    return;
  }

  var config = {};

  try {
    config = JSON.parse(configNode.textContent || '{}');
  } catch (error) {
    config = {};
  }

  var panels = document.querySelectorAll('.starterkit-type-panel');

  function updatePanels(type) {
    panels.forEach(function (panel) {
      panel.hidden = panel.getAttribute('data-section-type') !== type;
    });
  }

  function updateSlots(type) {
    var slots = config[type] && Array.isArray(config[type].allowed_slots) ? config[type].allowed_slots : [];
    var current = slotSelect.value;

    slotSelect.innerHTML = '';

    slots.forEach(function (slot) {
      var option = document.createElement('option');
      option.value = slot;
      option.textContent = slot;
      if (slot === current) {
        option.selected = true;
      }
      slotSelect.appendChild(option);
    });

    if (!slots.length) {
      var emptyOption = document.createElement('option');
      emptyOption.value = '';
      emptyOption.textContent = 'No slots available';
      slotSelect.appendChild(emptyOption);
    } else if (!slots.includes(current)) {
      slotSelect.value = slots[0];
    }
  }

  function sync() {
    var type = typeSelect.value;
    updatePanels(type);
    updateSlots(type);
  }

  function updateRowNumbers(group) {
    group.querySelectorAll('.starterkit-list-item').forEach(function (row, index) {
      var label = row.querySelector('.starterkit-list-row-number');
      if (label) {
        label.textContent = String(index + 1);
      }
    });
  }

  function bindRepeater(group) {
    var itemsContainer = group.querySelector('.starterkit-list-items');
    var templateNode = group.querySelector('.starterkit-list-template');
    var addButton = group.querySelector('.starterkit-add-row');

    if (!itemsContainer || !templateNode || !addButton) {
      return;
    }

    addButton.addEventListener('click', function () {
      var nextIndex = Number(itemsContainer.getAttribute('data-next-index') || '0');
      var html = templateNode.innerHTML.replace(/__INDEX__/g, String(nextIndex));
      itemsContainer.insertAdjacentHTML('beforeend', html);
      itemsContainer.setAttribute('data-next-index', String(nextIndex + 1));
      updateRowNumbers(group);
    });

    group.addEventListener('click', function (event) {
      var removeButton = event.target.closest('.starterkit-remove-row');
      var moveUpButton = event.target.closest('.starterkit-move-up');
      var moveDownButton = event.target.closest('.starterkit-move-down');

      if (moveUpButton || moveDownButton) {
        var movingRow = event.target.closest('.starterkit-list-item');
        if (!movingRow) {
          return;
        }
        if (moveUpButton && movingRow.previousElementSibling) {
          itemsContainer.insertBefore(movingRow, movingRow.previousElementSibling);
        }
        if (moveDownButton && movingRow.nextElementSibling) {
          itemsContainer.insertBefore(movingRow.nextElementSibling, movingRow);
        }
        updateRowNumbers(group);
        return;
      }

      if (!removeButton) {
        return;
      }

      var row = removeButton.closest('.starterkit-list-item');
      if (!row) {
        return;
      }

      row.remove();
      updateRowNumbers(group);
    });

    updateRowNumbers(group);
  }

  function bindMediaField(field) {
    var selectButton = field.querySelector('.starterkit-media-select');
    var clearButton = field.querySelector('.starterkit-media-clear');
    var hiddenInput = field.querySelector('input[type="hidden"]');
    var preview = field.querySelector('.starterkit-media-preview');

    if (!selectButton || !hiddenInput || !preview || typeof wp === 'undefined' || !wp.media) {
      return;
    }

    selectButton.addEventListener('click', function () {
      var frame = wp.media({
        title: 'Choose image',
        button: { text: 'Use image' },
        library: { type: 'image' },
        multiple: false
      });

      frame.on('select', function () {
        var attachment = frame.state().get('selection').first().toJSON();
        hiddenInput.value = attachment.id || '';
        preview.innerHTML = attachment.url ? '<img src="' + attachment.url + '" alt="">' : '<span>No image selected</span>';
      });

      frame.open();
    });

    if (clearButton) {
      clearButton.addEventListener('click', function () {
        hiddenInput.value = '';
        preview.innerHTML = '<span>No image selected</span>';
      });
    }
  }

  function bindValidation() {
    document.querySelectorAll('input[type="url"]').forEach(function (field) {
      field.addEventListener('blur', function () {
        field.classList.toggle('starterkit-invalid', field.value !== '' && !field.checkValidity());
      });
    });
  }

  typeSelect.addEventListener('change', sync);
  document.querySelectorAll('.starterkit-list-group').forEach(bindRepeater);
  document.querySelectorAll('.starterkit-media-field').forEach(bindMediaField);
  bindValidation();
  sync();
});
