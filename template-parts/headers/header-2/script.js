document.addEventListener('DOMContentLoaded', function () {
  var header = document.querySelector('.site-header--preset-2');

  if (!header) {
    return;
  }

  var toggle = header.querySelector('.site-header__toggle');
  var panel = header.querySelector('.site-header__panel');
  var searchToggle = header.querySelector('.header-search-toggle');
  var searchPanel = document.getElementById('site-header-search-2');

  if (toggle && panel) {
    toggle.addEventListener('click', function () {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
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

  var onScroll = function () {
    header.classList.toggle('is-scrolled', window.scrollY > 10);
  };

  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
});
