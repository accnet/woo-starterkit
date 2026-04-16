document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.starterkit-section--newsletter form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
    });
  });
});
