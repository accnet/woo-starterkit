(function () {
  'use strict';

  var initAttempts = 0;

  function escapeAttribute(value) {
    return String(value || '').replace(/"/g, '&quot;');
  }

  function initProductGallery() {
    var gallery = document.querySelector('.starterkit-product-layout.product-layout-1 .woocommerce-product-gallery');
    if (!gallery || gallery.dataset.starterkitSwiperReady === 'true') {
      return;
    }

    if (typeof window.Swiper === 'undefined') {
      if (initAttempts < 20) {
        initAttempts += 1;
        window.setTimeout(initProductGallery, 100);
      }
      return;
    }

    var wrapper = gallery.querySelector('.woocommerce-product-gallery__wrapper');
    if (!wrapper) {
      return;
    }

    var slides = Array.prototype.slice.call(wrapper.children).filter(function (child) {
      return child.classList.contains('woocommerce-product-gallery__image');
    });

    if (!slides.length) {
      return;
    }

    gallery.classList.add('starterkit-product-gallery--swiper');

    var main = document.createElement('div');
    main.className = 'swiper starterkit-product-gallery__main';

    var mainWrapper = document.createElement('div');
    mainWrapper.className = 'swiper-wrapper';

    slides.forEach(function (slide) {
      var clonedSlide = slide.cloneNode(true);
      clonedSlide.classList.add('swiper-slide');
      mainWrapper.appendChild(clonedSlide);
    });

    main.appendChild(mainWrapper);
    wrapper.replaceWith(main);

    var thumbsSwiper = null;
    var thumbSlides = slides
      .map(function (slide) {
        var thumbUrl = slide.getAttribute('data-thumb');
        var fullLink = slide.querySelector('a');
        var image = slide.querySelector('img');

        if (!thumbUrl && image) {
          thumbUrl = image.getAttribute('src') || '';
        }

        if (!thumbUrl) {
          return null;
        }

        return {
          thumbUrl: thumbUrl,
          alt: image ? (image.getAttribute('alt') || '') : '',
          href: fullLink ? (fullLink.getAttribute('href') || '') : ''
        };
      })
      .filter(Boolean);

    if (thumbSlides.length > 1) {
      var thumbs = document.createElement('div');
      thumbs.className = 'swiper starterkit-product-gallery__thumbs';

      var thumbsWrapper = document.createElement('div');
      thumbsWrapper.className = 'swiper-wrapper';

      thumbSlides.forEach(function (thumb) {
        var thumbSlide = document.createElement('div');
        thumbSlide.className = 'swiper-slide';
        thumbSlide.innerHTML =
          '<button class="starterkit-product-gallery__thumb-button" type="button" aria-label="Select product image">' +
          '<img src="' + escapeAttribute(thumb.thumbUrl) + '" alt="' + escapeAttribute(thumb.alt) + '">' +
          '</button>';
        thumbsWrapper.appendChild(thumbSlide);
      });

      thumbs.appendChild(thumbsWrapper);
      gallery.appendChild(thumbs);

      thumbsSwiper = new window.Swiper(thumbs, {
        direction: 'horizontal',
        slidesPerView: 4,
        spaceBetween: 12,
        freeMode: true,
        watchSlidesProgress: true,
        breakpoints: {
          0: {
            direction: 'horizontal',
            slidesPerView: 4
          },
          768: {
            direction: 'vertical',
            slidesPerView: 5
          }
        }
      });
    }

    new window.Swiper(main, {
      slidesPerView: 1,
      spaceBetween: 0,
      speed: 450,
      autoHeight: true,
      thumbs: thumbsSwiper ? { swiper: thumbsSwiper } : undefined
    });

    gallery.classList.toggle('starterkit-product-gallery--has-thumbs', !!thumbsSwiper);
    gallery.dataset.starterkitSwiperReady = 'true';
    initAttempts = 0;
  }

  function boot() {
    initProductGallery();

    if (typeof window.jQuery !== 'undefined') {
      window.jQuery(document.body).on('found_variation reset_image woocommerce_gallery_init', function () {
        window.requestAnimationFrame(initProductGallery);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
