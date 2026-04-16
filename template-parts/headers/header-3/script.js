document.addEventListener('DOMContentLoaded', function () {
  var header = document.querySelector('.site-header--preset-3');

  if (!header) {
    return;
  }

  var menuToggle = header.querySelector('.site-header__toggle');
  var menuPanel = header.querySelector('.site-header__panel');
  var searchToggle = header.querySelector('.header-search-toggle');
  var searchPanel = header.querySelector('.header-search-panel');

  if (menuToggle && menuPanel) {
    menuToggle.addEventListener('click', function () {
      var expanded = menuToggle.getAttribute('aria-expanded') === 'true';
      menuToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      header.classList.toggle('is-menu-open', !expanded);
    });
  }

  if (searchToggle && searchPanel) {
    searchToggle.addEventListener('click', function () {
      var expanded = searchToggle.getAttribute('aria-expanded') === 'true';
      searchToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      searchPanel.hidden = expanded;
    });
  }
});
