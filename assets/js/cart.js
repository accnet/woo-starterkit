/**
 * Cart page interactions.
 *
 * @package StarterKit
 */
(function () {
  'use strict';

  var form = document.querySelector('.starterkit-cart-items');
  if (!form) return;

  /* Auto-submit quantity changes after a short debounce. */
  var debounce = null;

  form.addEventListener('change', function (e) {
    if (!e.target.matches('.qty, input[type="number"]')) return;

    clearTimeout(debounce);
    debounce = setTimeout(function () {
      var updateBtn = form.querySelector('[name="update_cart"]');
      if (updateBtn) {
        updateBtn.disabled = false;
        updateBtn.click();
      }
    }, 600);
  });

  /* Highlight row on quantity change. */
  form.addEventListener('input', function (e) {
    if (!e.target.matches('.qty, input[type="number"]')) return;

    var row = e.target.closest('.starterkit-cart-items__row');
    if (row) {
      row.style.opacity = '0.6';
    }
  });
})();
