document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.starterkit-section--hero').forEach(function (section) {
    section.setAttribute('data-section-ready', 'true');
  });
});
