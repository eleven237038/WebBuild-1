/**
 * GenScript Login / Registration Page Helper (Static Deploy)
 * ===========================================================
 * Adapted from modules/login-manager.js for Vercel static deployment.
 *
 * Features:
 *   1. Language-switching dropdown
 *   2. Social-sharing hover menu
 *   3. Return-URL injection into login/signup links
 *
 * Links point to the real GenScript site since auth requires a live backend.
 *
 * @module login-manager
 * @deprecated Legacy jQuery code. Use framework-based auth for new development.
 */

'use strict';

jQuery(function ($) {

  // =========================================================================
  // 1. Language Dropdown
  // =========================================================================
  var $langTrigger = $('.dropdown dt a');
  var $langMenu = $('.dropdown dd ul');
  var $langItems = $('.dropdown dd ul li a');

  $langTrigger.on('click', function () {
    $langMenu.toggle();
  });

  $langItems.on('click', function () {
    var text = $(this).html();
    $langTrigger.html(text);
    $langMenu.hide();
    navigateToLanguage('newlan');
  });

  // Close dropdown when clicking outside
  $(document).on('click', function (e) {
    var $clicked = $(e.target);
    if (!$clicked.parents().hasClass('dropdown')) {
      $langMenu.hide();
    }
  });

  /**
   * Navigate to a locale-specific URL.
   * Reads the URL from the hidden span.value inside the dropdown.
   * @param {string} containerId — e.g. 'newlan'
   */
  function navigateToLanguage(containerId) {
    var url = $('#' + containerId).find('dt a span.value').html();
    if (url) {
      window.location.href = url;
    }
  }

  // =========================================================================
  // 2. Social Sharing Hover Menu
  // =========================================================================
  var $socialTrigger = $('#newsoical');
  var $socialMenu = $('.sodown dd ul');
  var $socialItems = $('.sodown dd ul li a');

  $socialTrigger.on('mouseover', function () {
    $socialMenu.show();
  });

  $socialTrigger.on('mouseout', function () {
    $socialMenu.hide();
  });

  $socialItems.on('click', function () {
    $socialMenu.hide();
  });

  // =========================================================================
  // 3. Return-URL Injection (Redirect-back-after-login pattern)
  // =========================================================================
  // When a user clicks Sign In or Register, we append ?return=<current page>
  // so the GenScript server can redirect them back after authentication.
  $('[href*="/customer/login"], [href*="/customer/signup"]').each(function () {
    var $link = $(this);
    var href = $link.attr('href');

    // Only modify if the href hasn't already been modified
    if (href.indexOf('?return=') === -1) {
      $link.attr('href', href + '?return=' + encodeURIComponent(window.location.href));
    }
  });

});
