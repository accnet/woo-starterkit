(function() {
  function activateTab(container, nextTab) {
    var tabs = Array.prototype.slice.call(container.querySelectorAll('[data-starterkit-tab]'));
    var panels = Array.prototype.slice.call(container.querySelectorAll('[data-starterkit-tab-panel]'));
    var panelId = nextTab.getAttribute('aria-controls');

    tabs.forEach(function(tab) {
      var isActive = tab === nextTab;

      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      tab.setAttribute('tabindex', isActive ? '0' : '-1');
    });

    panels.forEach(function(panel) {
      var isActive = panel.id === panelId;

      panel.classList.toggle('is-active', isActive);

      if (isActive) {
        panel.removeAttribute('hidden');
      } else {
        panel.setAttribute('hidden', '');
      }
    });
  }

  function onKeydown(event) {
    var tab = event.target.closest('[data-starterkit-tab]');

    if (!tab) {
      return;
    }

    var container = tab.closest('[data-starterkit-tabs]');
    var tabs = Array.prototype.slice.call(container.querySelectorAll('[data-starterkit-tab]'));
    var currentIndex = tabs.indexOf(tab);
    var nextIndex = currentIndex;

    if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
      nextIndex = (currentIndex + 1) % tabs.length;
    } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
      nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
    } else if (event.key !== 'Home' && event.key !== 'End') {
      return;
    }

    if (event.key === 'Home') {
      nextIndex = 0;
    }

    if (event.key === 'End') {
      nextIndex = tabs.length - 1;
    }

    event.preventDefault();
    activateTab(container, tabs[nextIndex]);
    tabs[nextIndex].focus();
  }

  document.addEventListener('click', function(event) {
    var tab = event.target.closest('[data-starterkit-tab]');

    if (!tab) {
      return;
    }

    var container = tab.closest('[data-starterkit-tabs]');

    if (!container) {
      return;
    }

    activateTab(container, tab);
  });

  document.addEventListener('keydown', onKeydown);
})();
