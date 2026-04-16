document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.starterkit-section--testimonials .testimonial-card').forEach(function (card) {
    card.setAttribute('data-card-ready', 'true');
  });
});
