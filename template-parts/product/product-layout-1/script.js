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

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
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

    gallery.starterkitBaseSlidesSignature = getSlidesSignature(gallery.starterkitBaseSlides);
    gallery.dataset.starterkitGallerySignature = gallery.starterkitBaseSlidesSignature;
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

  function syncDesktopThumbsHeight(gallery) {
    if (!gallery) {
      return;
    }

    var thumbs = gallery.querySelector('.starterkit-product-gallery__thumbs');
    var stage = gallery.querySelector('.starterkit-product-gallery__stage');

    if (!thumbs || !stage) {
      return;
    }

    if (window.innerWidth < 768) {
      thumbs.style.height = '';
      return;
    }

    var stageHeight = Math.round(stage.getBoundingClientRect().height);
    thumbs.style.height = stageHeight > 0 ? stageHeight + 'px' : '';
  }

  function scheduleGalleryMeasurement(gallery) {
    if (!gallery) {
      return;
    }

    window.requestAnimationFrame(function () {
      syncDesktopThumbsHeight(gallery);

      if (gallery.starterkitMainSwiper) {
        gallery.starterkitMainSwiper.update();
      }

      if (gallery.starterkitThumbsSwiper) {
        gallery.starterkitThumbsSwiper.update();
      }
    });
  }

  function setGalleryImageReadyState(image, isReady) {
    if (!image) {
      return;
    }

    image.dataset.starterkitImageReady = isReady ? 'true' : 'false';

    var wrapper = image.closest(
      '.starterkit-product-gallery__image-link, .starterkit-product-gallery__thumb-button'
    );

    if (!wrapper) {
      return;
    }

    wrapper.classList.toggle('is-image-loading', !isReady);
  }

  function bindGalleryImageLoadSync(gallery) {
    if (!gallery) {
      return;
    }

    var images = gallery.querySelectorAll(
      '.starterkit-product-gallery__image-image, .starterkit-product-gallery__thumb-image'
    );

    images.forEach(function (image) {
      setGalleryImageReadyState(image, false);

      if (image.dataset.starterkitLoadSyncBound === 'true') {
        if (image.complete) {
          setGalleryImageReadyState(image, true);
          scheduleGalleryMeasurement(gallery);
        }
        return;
      }

      image.dataset.starterkitLoadSyncBound = 'true';

      if (image.complete) {
        setGalleryImageReadyState(image, true);
        scheduleGalleryMeasurement(gallery);
        return;
      }

      image.addEventListener('load', function () {
        setGalleryImageReadyState(image, true);
        scheduleGalleryMeasurement(gallery);
      }, { once: true });

      image.addEventListener('error', function () {
        setGalleryImageReadyState(image, true);
        scheduleGalleryMeasurement(gallery);
      }, { once: true });
    });
  }

  function syncActiveThumbState(gallery, index) {
    var thumbs = gallery ? gallery.querySelector('.starterkit-product-gallery__thumbs') : null;

    if (!thumbs) {
      return;
    }

    var thumbSlides = thumbs.querySelectorAll('.swiper-slide');
    var activeSlide = null;
    var wasActive = false;

    thumbSlides.forEach(function (slide, slideIndex) {
      var isActive = slideIndex === index;

      if (isActive && slide.classList.contains('swiper-slide-thumb-active')) {
        wasActive = true;
      }

      slide.classList.toggle('swiper-slide-thumb-active', isActive);

      if (isActive) {
        activeSlide = slide;
      }
    });

    if (!activeSlide) {
      return;
    }

    if (gallery.starterkitThumbsSwiper) {
      if (wasActive && gallery.starterkitThumbsSwiper.activeIndex === index) {
        return;
      }

      gallery.starterkitThumbsSwiper.slideTo(index);
      return;
    }

    if (wasActive) {
      return;
    }

    activeSlide.scrollIntoView({
      block: 'nearest',
      inline: 'nearest',
      behavior: 'smooth'
    });
  }

  function goToGallerySlide(gallery, index) {
    if (!gallery || !gallery.starterkitMainSwiper) {
      return false;
    }

    var slideCount = gallery.starterkitMainSwiper.slides ? gallery.starterkitMainSwiper.slides.length : 0;
    var nextIndex = Math.max(0, Math.min(Number(index) || 0, Math.max(slideCount - 1, 0)));
    var currentIndex = Number(gallery.starterkitMainSwiper.activeIndex) || 0;

    if (currentIndex === nextIndex) {
      syncActiveThumbState(gallery, nextIndex);
      return false;
    }

    gallery.starterkitMainSwiper.slideTo(nextIndex);
    syncActiveThumbState(gallery, nextIndex);
    return true;
  }

  function mountSlides(gallery, slides) {
    var mainWrapper = gallery.querySelector('.starterkit-product-gallery__main .swiper-wrapper');
    var thumbs = gallery.querySelector('.starterkit-product-gallery__thumbs');
    var thumbsWrapper = thumbs ? thumbs.querySelector('.swiper-wrapper') : null;
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

    bindGalleryImageLoadSync(gallery);
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
    var hasThumbs = thumbs && !thumbs.hidden;
    var thumbsSwiper = null;

    function shouldUseThumbsSwiper() {
      if (!hasThumbs || !thumbs) {
        return false;
      }

      if (window.innerWidth >= 768) {
        return false;
      }

      var wrapper = thumbs.querySelector('.swiper-wrapper');
      if (!wrapper) {
        return false;
      }

      return wrapper.scrollWidth - thumbs.clientWidth > 2;
    }

    function bindThumbClicks() {
      if (!thumbs) {
        return;
      }

      if (gallery.starterkitThumbClickHandler) {
        thumbs.removeEventListener('click', gallery.starterkitThumbClickHandler);
      }

      gallery.starterkitThumbClickHandler = function (event) {
        var button = event.target.closest('.starterkit-product-gallery__thumb-button');
        if (!button) {
          return;
        }

        var slide = button.closest('.swiper-slide');
        if (!slide) {
          return;
        }

        var slides = Array.prototype.slice.call(
          thumbs.querySelectorAll('.swiper-slide')
        );
        var index = slides.indexOf(slide);

        if (index < 0 || !gallery.starterkitMainSwiper) {
          return;
        }

        event.preventDefault();
        goToGallerySlide(gallery, index);
      };

      thumbs.addEventListener('click', gallery.starterkitThumbClickHandler);
    }

    if (hasThumbs) {
      var useThumbsSwiper = shouldUseThumbsSwiper();
      thumbs.classList.toggle('is-static', !useThumbsSwiper && window.innerWidth < 768);

      if (useThumbsSwiper) {
      thumbsSwiper = new window.Swiper(thumbs, {
        direction: 'horizontal',
        slidesPerView: 'auto',
        spaceBetween: 0,
        freeMode: true,
        watchSlidesProgress: true,
        watchOverflow: true,
        breakpoints: {
          0: {
            direction: 'horizontal',
            slidesPerView: 'auto',
            spaceBetween: 0,
            freeMode: true
          },
          768: {
            direction: 'vertical',
            slidesPerView: 5,
            spaceBetween: 12,
            freeMode: true
          }
        }
      });
      }
    }

    gallery.starterkitMainSwiper = new window.Swiper(main, {
      slidesPerView: 1,
      speed: 500,
      spaceBetween: 0,
      watchOverflow: true,
      observer: true,
      observeParents: true,
      thumbs: thumbsSwiper ? { swiper: thumbsSwiper } : undefined,
      on: {
        init: function (swiper) {
          syncActiveThumbState(gallery, swiper.activeIndex || 0);
        },
        slideChange: function (swiper) {
          syncActiveThumbState(gallery, swiper.activeIndex || 0);
        }
      }
    });

    gallery.starterkitThumbsSwiper = thumbsSwiper;
    bindThumbClicks();
    gallery.dataset.starterkitSwiperReady = 'true';
    initAttempts = 0;

    scheduleGalleryMeasurement(gallery);
  }

  function getSlidesSignature(slides) {
    if (!Array.isArray(slides) || !slides.length) {
      return '';
    }

    return slides.map(function (slide) {
      return [
        Number(slide.imageId || 0),
        slide.normalizedImageSrc || normalizeUrl(slide.imageSrc || '')
      ].join(':');
    }).join('|');
  }

  function setSlidesForGallery(gallery, slides) {
    var nextSignature = getSlidesSignature(slides);

    if (gallery.dataset.starterkitGallerySignature === nextSignature) {
      return false;
    }

    destroySwipers(gallery);
    mountSlides(gallery, slides);
    gallery.dataset.starterkitGallerySignature = nextSignature;
    initializeSwipers(gallery);
    return true;
  }

  function buildDynamicSlide(imageUrl, variantId, isFeatured) {
    var src = String(imageUrl || '');
    var safeSrc = escapeHtml(src);
    var variantIds = variantId ? [Number(variantId) || 0].filter(Boolean) : [];
    var featuredVariantIds = isFeatured ? variantIds.slice() : [];
    var variantIdsJson = escapeHtml(JSON.stringify(variantIds));
    var featuredVariantIdsJson = escapeHtml(JSON.stringify(featuredVariantIds));

    return {
      imageId: 0,
      imageSrc: src,
      normalizedImageSrc: normalizeUrl(src),
      variantIds: variantIds,
      featuredVariantIds: featuredVariantIds,
      mainHtml:
        '<div class="swiper-slide" data-image-id="0" data-image-src="' + safeSrc + '" data-variant-ids="' + variantIdsJson + '" data-featured-variant-ids="' + featuredVariantIdsJson + '">' +
          '<div class="starterkit-product-gallery__image-link">' +
            '<img class="starterkit-product-gallery__image-image" src="' + safeSrc + '" alt="" loading="lazy" decoding="async" fetchpriority="auto">' +
          '</div>' +
        '</div>',
      thumbHtml:
        '<div class="swiper-slide" data-image-id="0" data-image-src="' + safeSrc + '" data-variant-ids="' + variantIdsJson + '" data-featured-variant-ids="' + featuredVariantIdsJson + '">' +
          '<button class="starterkit-product-gallery__thumb-button" type="button" aria-label="">' +
            '<img class="starterkit-product-gallery__thumb-image" src="' + safeSrc + '" alt="" loading="lazy" decoding="async">' +
          '</button>' +
        '</div>'
    };
  }

  function orderVariantGalleryUrls(gallery, imageUrls) {
    var baseSlides = gallery.starterkitBaseSlides || [];
    var fallbackOrder = {};

    if (!Array.isArray(imageUrls) || !imageUrls.length) {
      return [];
    }

    imageUrls.forEach(function (url, index) {
      fallbackOrder[normalizeUrl(url)] = index;
    });

    return imageUrls.slice().sort(function (leftUrl, rightUrl) {
      var leftNormalized = normalizeUrl(leftUrl);
      var rightNormalized = normalizeUrl(rightUrl);
      var leftBaseIndex = baseSlides.findIndex(function (slide) {
        return slide.normalizedImageSrc === leftNormalized;
      });
      var rightBaseIndex = baseSlides.findIndex(function (slide) {
        return slide.normalizedImageSrc === rightNormalized;
      });
      var leftHasBaseIndex = leftBaseIndex >= 0;
      var rightHasBaseIndex = rightBaseIndex >= 0;

      if (leftHasBaseIndex && rightHasBaseIndex) {
        return leftBaseIndex - rightBaseIndex;
      }

      if (leftHasBaseIndex) {
        return -1;
      }

      if (rightHasBaseIndex) {
        return 1;
      }

      return (fallbackOrder[leftNormalized] || 0) - (fallbackOrder[rightNormalized] || 0);
    });
  }

  function buildVariantSlides(gallery, imageUrls, variantId, featuredSrc) {
    var baseSlides = gallery.starterkitBaseSlides || [];
    var featuredNormalizedSrc = normalizeUrl(featuredSrc || '');
    var uniqueUrls = Array.isArray(imageUrls)
      ? imageUrls.map(function (url) {
        return String(url || '').trim();
      }).filter(Boolean).filter(function (url, index, list) {
        return list.indexOf(url) === index;
      })
      : [];
    var orderedUrls = orderVariantGalleryUrls(gallery, uniqueUrls);

    return orderedUrls.map(function (url, index) {
      var normalizedSrc = normalizeUrl(url);
      var matchedSlide = baseSlides.find(function (slide) {
        return slide.normalizedImageSrc === normalizedSrc;
      });

      if (matchedSlide) {
        return {
          imageId: matchedSlide.imageId,
          imageSrc: matchedSlide.imageSrc,
          normalizedImageSrc: matchedSlide.normalizedImageSrc,
          variantIds: variantId ? [variantId] : matchedSlide.variantIds,
          featuredVariantIds: (featuredNormalizedSrc && normalizedSrc === featuredNormalizedSrc && variantId) ? [variantId] : matchedSlide.featuredVariantIds,
          mainHtml: matchedSlide.mainHtml,
          thumbHtml: matchedSlide.thumbHtml
        };
      }

      return buildDynamicSlide(url, variantId, (featuredNormalizedSrc && normalizedSrc === featuredNormalizedSrc) || (!featuredNormalizedSrc && index === 0));
    });
  }

  function restoreBaseGallery(gallery, index) {
    if (!gallery || !gallery.starterkitBaseSlides || !gallery.starterkitBaseSlides.length) {
      return;
    }

    if (gallery.dataset.starterkitVariantGalleryActive === 'true') {
      setSlidesForGallery(gallery, gallery.starterkitBaseSlides);
      gallery.dataset.starterkitVariantGalleryActive = 'false';
    }

    goToGallerySlide(gallery, index || 0);
  }

  function replaceWithVariantGallery(gallery, slides, activeIndex) {
    if (!gallery || !Array.isArray(slides) || slides.length <= 1) {
      return false;
    }

    var nextIndex = Math.max(0, Math.min(Number(activeIndex) || 0, slides.length - 1));
    var nextSignature = getSlidesSignature(slides);

    if (nextSignature && nextSignature === gallery.starterkitBaseSlidesSignature) {
      restoreBaseGallery(gallery, nextIndex);
      return true;
    }

    if (
      gallery.dataset.starterkitVariantGalleryActive === 'true' &&
      gallery.dataset.starterkitGallerySignature === nextSignature
    ) {
      goToGallerySlide(gallery, nextIndex);
      return true;
    }

    setSlidesForGallery(gallery, slides);
    gallery.dataset.starterkitVariantGalleryActive = 'true';
    goToGallerySlide(gallery, nextIndex);

    return true;
  }

  function findSlideIndex(gallery, variation) {
    var baseSlides = gallery.starterkitBaseSlides || [];
    var variationId = getVariationId(variation);
    var variationImageId = getVariationImageId(variation);
    var variationImageSrc = getVariationImageSrc(variation);
    var normalizedVariationImageSrc = normalizeUrl(variationImageSrc);

    if (!baseSlides.length) {
      return 0;
    }

    if (variationId) {
      var featuredIndex = baseSlides.findIndex(function (slide) {
        return slide.featuredVariantIds.indexOf(variationId) !== -1;
      });

      if (featuredIndex >= 0) {
        return featuredIndex;
      }

      var variantIndex = baseSlides.findIndex(function (slide) {
        return slide.variantIds.indexOf(variationId) !== -1;
      });

      if (variantIndex >= 0) {
        return variantIndex;
      }
    }

    if (variationImageId) {
      var imageIndex = baseSlides.findIndex(function (slide) {
        return slide.imageId === variationImageId;
      });

      if (imageIndex >= 0) {
        return imageIndex;
      }
    }

    if (normalizedVariationImageSrc) {
      var imageSrcIndex = baseSlides.findIndex(function (slide) {
        return slide.normalizedImageSrc === normalizedVariationImageSrc;
      });

      if (imageSrcIndex >= 0) {
        return imageSrcIndex;
      }
    }

    return 0;
  }

  function syncGalleryToVariation(variation) {
    var gallery = document.querySelector('.starterkit-product-layout.product-layout-1 .starterkit-product-gallery');
    if (!gallery || !gallery.starterkitBaseSlides || !gallery.starterkitBaseSlides.length) {
      return;
    }

    restoreBaseGallery(gallery, findSlideIndex(gallery, variation));
  }

  function syncGalleryToWootifyVariant(detail) {
    var gallery = document.querySelector('.starterkit-product-layout.product-layout-1 .starterkit-product-gallery');
    if (!gallery || !gallery.starterkitBaseSlides || !gallery.starterkitBaseSlides.length) {
      return;
    }

    if (!detail || typeof detail !== 'object') {
      restoreBaseGallery(gallery, 0);
      return;
    }

    var payload = detail.theme_gallery && typeof detail.theme_gallery === 'object' ? detail.theme_gallery : detail;
    var variantId = Number(payload.variant_id || detail.id || detail.variant_id || 0);
    var featuredImage = payload.featured_image || detail.selected_image_url || detail.image_url || (detail.featured_image && detail.featured_image.src) || '';
    var galleryImages = Array.isArray(payload.gallery_images)
      ? payload.gallery_images.filter(Boolean)
      : (Array.isArray(detail.selected_gallery_images) ? detail.selected_gallery_images.filter(Boolean) : []);

    if (galleryImages.length > 1) {
      var variantSlides = buildVariantSlides(gallery, galleryImages, variantId, featuredImage);
      var activeVariantIndex = variantSlides.findIndex(function (slide) {
        return slide.featuredVariantIds.indexOf(variantId) !== -1;
      });

      if (activeVariantIndex < 0 && featuredImage) {
        var normalizedFeaturedImage = normalizeUrl(featuredImage);

        activeVariantIndex = variantSlides.findIndex(function (slide) {
          return slide.normalizedImageSrc === normalizedFeaturedImage;
        });
      }

      if (replaceWithVariantGallery(gallery, variantSlides, activeVariantIndex)) {
        return;
      }
    }

    restoreBaseGallery(gallery, findSlideIndex(gallery, {
      variation_id: variantId,
      image_id: Number(payload.featured_image_id || detail.image_id || 0),
      image: {
        src: featuredImage
      }
    }));
  }

  function initProductGallery(gallery) {
    if (!gallery || gallery.dataset.starterkitGalleryBooted === 'true') {
      return;
    }

    collectBaseSlides(gallery);
    initializeSwipers(gallery);
    bindGalleryImageLoadSync(gallery);
    gallery.dataset.starterkitGalleryBooted = 'true';
  }

  function boot() {
    var galleries = document.querySelectorAll('.starterkit-product-layout.product-layout-1 .starterkit-product-gallery');

    galleries.forEach(function (gallery) {
      initProductGallery(gallery);
    });

    window.addEventListener('resize', function () {
      galleries.forEach(function (gallery) {
        scheduleGalleryMeasurement(gallery);
      });
    });

    window.addEventListener('load', function () {
      galleries.forEach(function (gallery) {
        scheduleGalleryMeasurement(gallery);
      });
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
