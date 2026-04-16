document.addEventListener('DOMContentLoaded', function () {
  var footer = document.querySelector('.site-footer--preset-1');

  if (!footer) {
    return;
  }

  footer.setAttribute('data-footer-ready', 'true');
});
