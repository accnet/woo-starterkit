(function () {
  function syncToggleState(container, expanded) {
    var content = container.querySelector('.starterkit-element-description__body');
    var button = container.querySelector('[data-starterkit-description-toggle]');
    var label = button ? button.querySelector('.starterkit-element-description__toggle-label') : null;

    if (!content || !button || !label) {
      return;
    }

    content.classList.toggle('is-collapsed', !expanded);
    container.classList.toggle('is-expanded', expanded);
    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    label.textContent = expanded ? (button.getAttribute('data-expanded-label') || '') : (button.getAttribute('data-collapsed-label') || '');
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-starterkit-description-toggle]');

    if (!button) {
      return;
    }

    var container = button.closest('[data-starterkit-description]');

    if (!container) {
      return;
    }

    event.preventDefault();
    syncToggleState(container, button.getAttribute('aria-expanded') !== 'true');
  });
})();