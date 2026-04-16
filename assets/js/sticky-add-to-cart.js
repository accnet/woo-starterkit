document.addEventListener('DOMContentLoaded', function () {
  var stickyBar = document.querySelector('[data-sticky-atc]');
  var originalForm = document.querySelector('.single-product form.cart');
  var summary = document.querySelector('.single-product .summary');
  var trigger = stickyBar ? stickyBar.querySelector('[data-sticky-atc-trigger]') : null;
  var qtyInput = stickyBar ? stickyBar.querySelector('.starterkit-sticky-atc__qty') : null;

  if (!stickyBar || !trigger || !summary) {
    return;
  }

  var setVisible = function (visible) {
    stickyBar.hidden = !visible;
  };

  var syncOriginalQuantity = function () {
    if (!qtyInput || !originalForm) {
      return;
    }

    var originalQty = originalForm.querySelector('input.qty');

    if (originalQty) {
      originalQty.value = qtyInput.value;
      originalQty.dispatchEvent(new Event('change', { bubbles: true }));
    }
  };

  var scrollToSummary = function () {
    summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
  };

  var onIntersection = function (entries) {
    entries.forEach(function (entry) {
      setVisible(!entry.isIntersecting);
    });
  };

  var observer = new IntersectionObserver(onIntersection, {
    threshold: 0.2,
  });

  observer.observe(summary);

  if (originalForm) {
    var originalQty = originalForm.querySelector('input.qty');

    if (originalQty && qtyInput) {
      qtyInput.value = originalQty.value || '1';
    }
  }

  trigger.addEventListener('click', function () {
    var actionMode = stickyBar.getAttribute('data-action-mode') || 'scroll';

    if (actionMode !== 'submit' || !originalForm) {
      scrollToSummary();
      return;
    }

    syncOriginalQuantity();
    trigger.classList.add('is-loading');
    scrollToSummary();

    var submitButton = originalForm.querySelector('.single_add_to_cart_button');

    if (submitButton) {
      submitButton.click();
    }
  });

  document.addEventListener('submit', function (event) {
    if (originalForm && event.target === originalForm) {
      trigger.classList.add('is-loading');
    }
  });

  if (window.jQuery) {
    window.jQuery(document.body).on('added_to_cart', function () {
      trigger.classList.remove('is-loading');
    });
  }

  originalForm && originalForm.addEventListener('change', function () {
    if (!qtyInput) {
      return;
    }

    var originalQty = originalForm.querySelector('input.qty');

    if (originalQty) {
      qtyInput.value = originalQty.value || '1';
    }
  });
});
