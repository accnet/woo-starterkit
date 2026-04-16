document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.starterkit-section--cta').forEach(function (section) {
    section.setAttribute('data-cta-mounted', 'true');
  });
});
