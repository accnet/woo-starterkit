document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.starterkit-section--faq details').forEach(function (item) {
    item.addEventListener('toggle', function () {
      item.setAttribute('data-open', item.open ? 'true' : 'false');
    });
  });
});
