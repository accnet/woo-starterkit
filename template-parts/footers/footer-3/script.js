document.addEventListener('DOMContentLoaded', function () {
  var footer = document.querySelector('.site-footer--preset-3');

  if (!footer) {
    return;
  }

  footer.setAttribute('data-footer-compact', 'true');
});
