document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.starterkit-section--features .feature-card').forEach(function (card, index) {
    card.style.transitionDelay = (index * 40) + 'ms';
  });
});
