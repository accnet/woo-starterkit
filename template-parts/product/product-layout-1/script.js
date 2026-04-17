(function () {
  'use strict';

  var initAttempts = 0;

  function parseIdList(value) {
    if (!value) {
      return [];
    }

    try {
      var parsed = JSON.parse(value);
      if (Array.isArray(parsed)) {
        return parsed.map(function (item) {
          return Number(item) || 0;
        }).filter(Boolean);
      }
    } catch (error) {
      return [];
    }

    return [];
  }

  function getVariationId(variation) {
    if (!variation || typeof variation !== 'object') {
      return 0;
    }

    if (variation.variation_id) {
      return Number(variation.variation_id) || 0;
    }

    if (variation.id) {
      return Number(variation.id) || 0;
    }

    return 0;
  }

  function getVariationImageId(variation) {
    if (!variation || typeof variation !== 'object') {
      return 0;
    }

    if (variation.image_id) {
      return Number(variation.image_id) || 0;
    }

    if (variation.image && variation.image.image_id) {
      return Number(variation.image.image_id) || 0;
    }

    if (variation.image && variation.image.attachment_id) {
      return Number(variation.image.attachment_id) || 0;
    }

    return 0;
  }

  function getVariationImageSrc(variation) {
    if (!variation || typeof variation !== 'object') {
      return '';
    }

    if (variation.image && variation.image.src) {
      return String(variation.image.src);
    }

    if (variation.featured_image && variation.featured_image.src) {
      return String(variation.featured_image.src);
    }

    if (variation.image_url) {
      return String(variation.image_url);
    }

    return '';
  }

  function normalizeUrl(url) {
    if (!url) {
      return '';
    }

    try {
      var parsed = new URL(String(url), window.location.origin);
      return (parsed.host + parsed.pathname).replace(/\/+$/, '').toLowerCase();
    } catch (error) {
      return String(url).trim().toLowerCase();
    }
  }

  function collectBaseSlides(gallery) {
    var mainSlides = Array.prototype.slice.call(
      gallery.querySelectorAll('.starterkit-product-gallery__main .swiper-slide')
    );
    var thumbSlides = Array.prototype.slice.call(
      gallery.querySelectorAll('.starterkit-product-gallery__thumbs .swiper-slide')
    );

    gallery.starterkitBaseSlides = mainSlides.map(function (slide, index) {
      var thumbSlide = thumbSlides[index] || null;

      return {
        imageId: Number(slide.dataset.imageId || 0),
        imageSrc: String(slide.dataset.imageSrc || ''),
        normalizedImageSrc: normalizeUrl(slide.dataset.imageSrc || ''),
        variantIds: parseIdList(slide.dataset.variantIds),
        featuredVariantIds: parseIdList(slide.dataset.featuredVariantIds),
        mainHtml: slide.outerHTML,
        thumbHtml: thumbSlide ? thumbSlide.outerHTML : ''
      };
    });
  }

  function destroySwipers(gallery) {
    if (gallery.starterkitMainSwiper) {
      gallery.starterkitMainSwiper.destroy(true, true);
      gallery.starterkitMainSwiper = null;
    }

    if (gallery.starterkitThumbsSwiper) {
      gallery.starterkitThumbsSwiper.destroy(true, true);
      gallery.starterkitThumbsSwiper = null;
    }
  }

  function mountSlides(gallery, slides) {
    var mainWrapper = gallery.querySelector('.starterkit-product-gallery__main .swiper-wrapper');
    var thumbs = gallery.querySelector('.starterkit-product-gallery__thumbs');
    var thumbsWrapper = thumbs ? thumbs.querySelector('.swiper-wrapper') : null;
    var prevButton = gallery.querySelector('.starterkit-product-gallery__nav--prev');
    var nextButton = gallery.querySelector('.starterkit-product-gallery__nav--next');
    var hasThumbs = slides.length > 1 && thumbsWrapper;

    if (mainWrapper) {
      mainWrapper.innerHTML = slides.map(function (slide) {
        return slide.mainHtml;
      }).join('');
    }

    if (thumbsWrapper) {
      thumbsWrapper.innerHTML = hasThumbs ? slides.map(function (slide) {
        return slide.thumbHtml;
      }).join('') : '';
    }

    gallery.classList.toggle('starterkit-product-gallery--has-thumbs', !!hasThumbs);

    if (thumbs) {
      thumbs.hidden = !hasThumbs;
    }

    if (prevButton) {
      prevButton.hidden = slides.length <= 1;
    }

    if (nextButton) {
      nextButton.hidden = slides.length <= 1;
    }
  }

  function initializeSwipers(gallery) {
    if (typeof window.Swiper === 'undefined') {
      if (initAttempts < 20) {
        initAttempts += 1;
        window.setTimeout(function () {
          initializeSwipers(gallery);
        }, 100);
      }
      return;
    }

    var main = gallery.querySelector('.starterkit-product-gallery__main');
    if (!main) {
      return;
    }

    var thumbs = gallery.querySelector('.starterkit-product-gallery__thumbs');
    var prevButton = gallery.querySelector('.starterkit-product-gallery__nav--prev');
    var nextButton = gallery.querySelector('.starterkit-product-gallery__nav--next');
    var hasThumbs = thumbs && !thumbs.hidden;
    var thumbsSwiper = null;

    if (hasThumbs) {
      thumbsSwiper = new window.Swiper(thumbs, {
        direction: 'horizontal',
        slidesPerView: 'auto',
        spaceBetween: 12,
        freeMode: true,
        watchSlidesProgress: true,
        watchOverflow: true,
        breakpoints: {
          0: {
            direction: 'horizontal',
            slidesPerView: 'auto'
          },
          768: {
            direction: 'vertical',
            slidesPerView: 5
          }
        }
      });
    }

    gallery.starterkitMainSwiper = new window.Swiper(main, {
      slidesPerView: 1,
      speed: 500,
      spaceBetween: 0,
      watchOverflow: true,
      observer: true,
      observeParents: true,
      navigation: prevButton && nextButton && !prevButton.hidden && !nextButton.hidden ? {
        prevEl: prevButton,
        nextEl: nextButton
      } : undefined,
      thumbs: thumbsSwiper ? { swiper: thumbsSwiper } : undefined
    });

    gallery.starterkitThumbsSwiper = thumbsSwiper;
    gallery.dataset.starterkitSwiperReady = 'true';
    initAttempts = 0;
  }

  function setSlidesForGallery(gallery, slides) {
    destroySwipers(gallery);
    mountSlides(gallery, slides);
    initializeSwipers(gallery);
  }

  function prioritizeSlides(slides, featuredSrc) {
    if (!featuredSrc) {
      return slides;
    }

    return slides.slice().sort(function (left, right) {
      if (left.normalizedImageSrc === featuredSrc) {
        return -1;
      }

      if (right.normalizedImageSrc === featuredSrc) {
        return 1;
      }

      return 0;
    });
  }

  function normalizeSlideSet(gallery, slides, featuredSrc) {
    var baseSlides = gallery.starterkitBaseSlides || [];
    var nextSlides = Array.isArray(slides) && slides.length ? slides : baseSlides;

    if (nextSlides.length <= 1 && baseSlides.length > 1) {
      nextSlides = baseSlides;
    }

    return prioritizeSlides(nextSlides, featuredSrc);
  }

  function getSlidesForVariation(gallery, variation) {
    var baseSlides = gallery.starterkitBaseSlides || [];
    var variationId = getVariationId(variation);
    var variationImageId = getVariationImageId(variation);
    var variationImageSrc = getVariationImageSrc(variation);
    var normalizedVariationImageSrc = normalizeUrl(variationImageSrc);

    if (!variationId) {
      return baseSlides;
    }

    var featuredSlides = baseSlides.filter(function (slide) {
      return slide.featuredVariantIds.indexOf(variationId) !== -1;
    });

    var variantSlides = baseSlides.filter(function (slide) {
      return slide.variantIds.indexOf(variationId) !== -1;
    });

    if (featuredSlides.length && variantSlides.length) {
      return featuredSlides.concat(variantSlides.filter(function (slide) {
        return featuredSlides.indexOf(slide) === -1;
      }));
    }

    if (featuredSlides.length > 1) {
      return featuredSlides;
    }

    if (variantSlides.length > 1) {
      return variantSlides;
    }

    if (variationImageId) {
      var imageSlides = baseSlides.filter(function (slide) {
        return slide.imageId === variationImageId;
      });

      if (imageSlides.length > 1) {
        return imageSlides;
      }
    }

    if (normalizedVariationImageSrc) {
      var imageSrcSlides = baseSlides.filter(function (slide) {
        return slide.normalizedImageSrc === normalizedVariationImageSrc;
      });

      if (imageSrcSlides.length > 1) {
        return imageSrcSlides;
      }
    }

    return baseSlides;
  }

  function syncGalleryToVariation(variation) {
    var gallery = document.querySelector('.starterkit-product-layout.product-layout-1 .starterkit-product-gallery');
    if (!gallery || !gallery.starterkitBaseSlides || !gallery.starterkitBaseSlides.length) {
      return;
    }

    var slides = getSlidesForVariation(gallery, variation);
    setSlidesForGallery(gallery, normalizeSlideSet(gallery, slides, normalizeUrl(getVariationImageSrc(variation))));
  }

  function syncGalleryToWootifyVariant(detail) {
    var gallery = document.querySelector('.starterkit-product-layout.product-layout-1 .starterkit-product-gallery');
    if (!gallery || !gallery.starterkitBaseSlides || !gallery.starterkitBaseSlides.length) {
      return;
    }

    if (!detail || typeof detail !== 'object') {
      setSlidesForGallery(gallery, gallery.starterkitBaseSlides);
      return;
    }

    var variantId = Number(detail.id || detail.variant_id || 0);
    var featuredSrc = normalizeUrl(
      detail.selected_image_url ||
      detail.image_url ||
      (detail.featured_image && detail.featured_image.src) ||
      ''
    );
    var gallerySrcs = Array.isArray(detail.selected_gallery_images)
      ? detail.selected_gallery_images.map(normalizeUrl).filter(Boolean)
      : [];

    var slides = [];

    if (gallerySrcs.length > 1) {
      slides = gallery.starterkitBaseSlides.filter(function (slide) {
        return gallerySrcs.indexOf(slide.normalizedImageSrc) !== -1;
      });
    }

    if (!slides.length && variantId) {
      slides = gallery.starterkitBaseSlides.filter(function (slide) {
        return slide.featuredVariantIds.indexOf(variantId) !== -1 || slide.variantIds.indexOf(variantId) !== -1;
      });
    }

    if (!slides.length && gallerySrcs.length === 1) {
      slides = gallery.starterkitBaseSlides.filter(function (slide) {
        return gallerySrcs.indexOf(slide.normalizedImageSrc) !== -1;
      });

      if (slides.length <= 1) {
        slides = gallery.starterkitBaseSlides;
      }
    }

    if (!slides.length && featuredSrc) {
      slides = gallery.starterkitBaseSlides.filter(function (slide) {
        return slide.normalizedImageSrc === featuredSrc;
      });

      if (slides.length <= 1) {
        slides = gallery.starterkitBaseSlides;
      }
    }

    if (!slides.length) {
      slides = gallery.starterkitBaseSlides;
    }

    setSlidesForGallery(gallery, normalizeSlideSet(gallery, slides, featuredSrc));
  }

  function initProductGallery(gallery) {
    if (!gallery || gallery.dataset.starterkitGalleryBooted === 'true') {
      return;
    }

    collectBaseSlides(gallery);
    initializeSwipers(gallery);
    gallery.dataset.starterkitGalleryBooted = 'true';
  }

  function boot() {
    var galleries = document.querySelectorAll('.starterkit-product-layout.product-layout-1 .starterkit-product-gallery');

    galleries.forEach(function (gallery) {
      initProductGallery(gallery);
    });

    if (typeof window.jQuery !== 'undefined') {
      window.jQuery(document.body).on('found_variation', '.variations_form', function (event, variation) {
        syncGalleryToVariation(variation);
      });

      window.jQuery(document.body).on('reset_image', '.variations_form', function () {
        syncGalleryToVariation(null);
      });
    }

    window.addEventListener('wootify:variant:changed', function (event) {
      syncGalleryToWootifyVariant(event.detail || null);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
