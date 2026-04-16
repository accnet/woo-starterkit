document.addEventListener('DOMContentLoaded', function () {
  var header = document.querySelector('.site-header--preset-1');

  if (!header) {
    return;
  }

  var menuToggle = header.querySelector('.site-header__toggle');
  var menuPanel = header.querySelector('.site-header__panel');
  var closeButton = header.querySelector('.site-header__close');
  var searchPanel = header.querySelector('.header-search-panel');
  var searchToggles = header.querySelectorAll('.header-search-toggle, .header-search-button');

  var setMenuState = function (open) {
    if (!menuToggle || !menuPanel) {
      return;
    }

    menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    header.classList.toggle('is-menu-open', open);
  };

  var setSearchState = function (open) {
    if (!searchPanel) {
      return;
    }

    searchPanel.hidden = !open;

    searchToggles.forEach(function (toggle) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  };

  if (menuToggle && menuPanel) {
    menuToggle.addEventListener('click', function () {
      var expanded = menuToggle.getAttribute('aria-expanded') === 'true';
      setMenuState(!expanded);
    });
  }

  if (closeButton) {
    closeButton.addEventListener('click', function () {
      setMenuState(false);
    });
  }

  searchToggles.forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      setSearchState(!expanded);
    });
  });

  document.addEventListener('click', function (event) {
    if (!header.contains(event.target)) {
      setMenuState(false);
      setSearchState(false);
    }
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth > 960) {
      setMenuState(false);
    }
  });
});
