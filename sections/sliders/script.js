(function () {
  function toNumber(value, fallback) {
    var parsed = parseInt(value, 10);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function initSlider(slider) {
    if (!slider || slider.dataset.starterkitSliderReady === 'true') {
      return;
    }

    if (typeof window.Swiper === 'undefined') {
      return;
    }

    var slideCount = slider.querySelectorAll('.swiper-slide').length;

    if (slideCount < 2) {
      return;
    }

    var autoplayDelay = toNumber(slider.dataset.autoplay, 5000);
    var speed = toNumber(slider.dataset.speed, 600);
    var pagination = slider.querySelector('.swiper-pagination');
    var next = slider.querySelector('.swiper-button-next');
    var prev = slider.querySelector('.swiper-button-prev');

    slider.starterkitSwiper = new window.Swiper(slider, {
      loop: slideCount > 1,
      speed: speed > 0 ? speed : 600,
      autoplay: autoplayDelay > 0 ? { delay: autoplayDelay, disableOnInteraction: false } : false,
      pagination: pagination ? { el: pagination, clickable: true } : undefined,
      navigation: next && prev ? { nextEl: next, prevEl: prev } : undefined
    });

    slider.dataset.starterkitSliderReady = 'true';
  }

  function initAll() {
    document.querySelectorAll('.js-starterkit-section-slider').forEach(initSlider);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();
