document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.starterkit-section--product-grid .starterkit-product-card').forEach(function (card) {
    card.setAttribute('data-product-card', 'true');
  });
});
