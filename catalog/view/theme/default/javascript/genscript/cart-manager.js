/**
 * GenScript Cart Manager (Static Deploy / Demo Mode)
 * ===================================================
 * Adapted from modules/cart-manager.js for Vercel static deployment.
 *
 * All JSONP API calls have been replaced with local mock data.
 * The demo cart always shows "0 items" — add-to-cart shows a
 * notification that this is a static replica.
 *
 * ## JSONP → $.getJSON migration
 *
 * The original cart endpoints (`/customer/cart/count`, `/customer/cart/get`,
 * `/customer/cart/add`) are JSONP services that require a live backend
 * and browser cookies.  On our static replica we serve mock JSON files.
 *
 * NOTE: The legacy `ct.*` aliases are preserved for any inline onclick
 * handlers that still reference the old variable name.
 *
 * @module CartManager
 */

'use strict';

var CartAPI = {};
var ct = CartAPI; // legacy alias for inline handlers

jQuery(function ($) {

  // =========================================================================
  // Configuration
  // =========================================================================
  var CONFIG = {
    cartContainer: '#get-box',
    cartIcon: '#get-box .my-cart',
    cartDropdown: '.my-cart-div',
    alertDialog: '#show_alert',
    btnAddToCart: '.btn-add-to-cart',
    btnAddToCartList: '.btn-add-to-cart2',
    btnViewCart: '.btn-show-to-cart',
    quickOrderForm: '.form-product-quick-list',
    maxDisplayCount: 100,        // "99+" above this
    mockCountUrl: 'data/cart-count.json',
    IS_DEMO: true,
  };

  // =========================================================================
  // Notification Helper (replaces alert())
  // =========================================================================
  var Notify = {
    info: function (msg) {
      // Try Bootstrap toast first; fall back to alert
      if (typeof $ !== 'undefined' && $.fn.toast) {
        // Bootstrap toast available
      }
      alert(msg);
    },
    error: function (msg) { alert('Error: ' + msg); }
  };

  // =========================================================================
  // Cart Badge (update the count shown in the header)
  // =========================================================================

  /**
   * Fetch the current cart count and update the badge.
   * In demo mode, always returns 0.
   */
  function updateCartBadge() {
    if (CONFIG.IS_DEMO) {
      // Static demo — cart is always empty
      $(CONFIG.cartIcon).html('');
      return;
    }

    $.getJSON(CONFIG.mockCountUrl)
      .done(function (d) {
        if (d.status && d.data !== undefined) {
          var n = d.data > CONFIG.maxDisplayCount
            ? '99+'
            : d.data;
          if (n > 0) {
            $(CONFIG.cartIcon).html('<b class="cart-number">' + n + '</b>');
          } else {
            $(CONFIG.cartIcon).html('');
          }
        }
      })
      .fail(function () {
        console.warn('[Cart] Could not fetch cart count.');
      });
  }

  // =========================================================================
  // Cart Dropdown (hover preview)
  // =========================================================================

  /**
   * Show a hover-dropdown preview of the cart contents.
   * In demo mode, shows "Your cart is empty."
   */
  function refreshCartDropdown($container) {
    var $dropdown = $container.find(CONFIG.cartDropdown);
    if ($dropdown.length > 0) {
      $dropdown.slideDown();
      return;
    }

    var dropdownHtml =
      '<div class="my-cart-div-box" style="position:absolute;right:0;top:100%;z-index:99999;">' +
      '<div class="my-cart-div" style="position:absolute;width:350px;background:#fff;border:1px solid #eef2f0;border-radius:4px;padding:10px;display:none;right:0;top:0;">' +
      '</div></div>';

    $container.prepend(dropdownHtml);
    var $cartDiv = $container.find(CONFIG.cartDropdown).html('Loading…').show();

    // In demo mode, always show empty cart
    var html =
      '<div style="cursor:pointer;font-size:30px;position:absolute;right:0;top:0;width:30px;height:30px;line-height:30px;text-align:center;" title="Close" onclick="$(\'.my-cart-div\').hide()">×</div>' +
      '<p style="color:#999;text-align:center;padding:20px 0;margin-bottom:0;">' +
      'Your cart is empty</p>' +
      '<p style="text-align:center;font-size:12px;color:#aaa;">' +
      '(Demo mode — no backend)</p>';

    $cartDiv.html(html).slideDown().hover(
      function () { },
      function () { $cartDiv.slideUp(); }
    );
  }

  // =========================================================================
  // Add to Cart (Demo Mode)
  // =========================================================================

  function addToCart(products) {
    if (CONFIG.IS_DEMO) {
      Notify.info(
        'Demo Mode\n\n' +
        'This is a static front-end replica.\n' +
        'In the real site, ' + products.length + ' item(s) would be added to your cart.\n\n' +
        'Visit www.genscript.com to place real orders.'
      );
      return;
    }

    // (Original JSONP logic would go here)
    Notify.info('Item(s) added to Cart successfully.');
  }

  // =========================================================================
  // Event Bindings
  // =========================================================================

  // -- Hover on cart icon to show dropdown preview --
  $(CONFIG.cartIcon).hover(
    function () {
      refreshCartDropdown($(CONFIG.cartContainer));
    },
    function () { /* keep open on hover-out; hides on its own */ }
  );

  // -- "View Cart" button --
  $(CONFIG.btnViewCart).on('click', function () {
    window.location.href = 'https://www.genscript.com/customer/cart';
    return false;
  });

  // -- "Add to Cart" on detail page --
  $(document).on('click', CONFIG.btnAddToCart, function () {
    var $me = $(this);
    var $container = $me.closest('table, .product-detail') || $me.parent().parent();
    var $checked = $container.find('table input:checked');
    var $qtyInputs = $container.find('table input[type="text"]');

    if ($checked.length === 0) {
      Notify.error('Please select at least one product.');
      return false;
    }

    var cartItems = [];
    $checked.each(function () {
      var pid = $(this).val();
      if (pid > 0) {
        var qty = $container.find('.pid-' + pid + ' input[type="text"]').val() || 1;
        cartItems.push({ pid: pid, qty: qty });
      }
    });

    addToCart(cartItems);
    return false;
  });

  // -- "Add to Cart" on list page --
  $(document).on('click', CONFIG.btnAddToCartList, function () {
    var $me = $(this);
    var $container = $me.closest('tr, .product-row') || $me.parent().parent();
    var $qtyInputs = $container.find('table input[type="text"]');

    var cartItems = [];
    var hasQuantity = false;
    $qtyInputs.each(function () {
      var qty = parseInt($(this).val(), 10);
      if (qty > 0) {
        hasQuantity = true;
        var pid = $(this).next().val();
        cartItems.push({ pid: pid, qty: qty });
      }
    });

    if (!hasQuantity) {
      Notify.error('Please enter at least 1 quantity.');
      return false;
    }

    addToCart(cartItems);
    return false;
  });

  // -- Quick Order form --
  $(document).on('submit', CONFIG.quickOrderForm, function () {
    if (CONFIG.IS_DEMO) {
      Notify.info('Demo Mode\n\nQuick ordering is not available on this static replica.');
      return false;
    }
    // Original quick-order logic goes here
    return false;
  });

  // =========================================================================
  // Initialize
  // =========================================================================
  updateCartBadge();

  // -- Attach public methods for backward compatibility --
  CartAPI.add = addToCart;
  CartAPI.updateCount = updateCartBadge;
  CartAPI.refreshDropdown = refreshCartDropdown;
  CartAPI.notify = Notify;

  // Legacy aliases (used by inline onclick handlers in the original HTML)
  ct.add = CartAPI.add;
  ct.update_num = CartAPI.updateCount;
  ct.refresh_cart_view = function (flag, idEl) {
    refreshCartDropdown($(idEl || CONFIG.cartContainer));
  };

});
