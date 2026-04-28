document.addEventListener('DOMContentLoaded', function () {
  var header = document.querySelector('.site-header--preset-2');

  if (!header) {
    return;
  }

  var toggle = header.querySelector('.site-header__toggle');
  var panel = header.querySelector('.site-header__panel');
  var backdrop = header.querySelector('.site-header__backdrop');
  var searchToggle = header.querySelector('.header-search-toggle');
  var searchPanel = document.getElementById('site-header-search-2');

  function setMenuOpen(open) {
    if (!toggle || !panel) {
      return;
    }

    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    header.classList.toggle('is-menu-open', open);
    document.documentElement.classList.toggle('has-mobile-menu-open', open);
    document.body.classList.toggle('has-mobile-menu-open', open);
  }

  if (toggle && panel) {
    toggle.addEventListener('click', function () {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      setMenuOpen(!expanded);
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', function () {
      setMenuOpen(false);
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      setMenuOpen(false);
    }
  });

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
