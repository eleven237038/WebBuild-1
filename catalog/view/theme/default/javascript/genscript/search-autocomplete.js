/**
 * GenScript Search Autocomplete Module (Static Deploy)
 * =====================================================
 * Adapted from modules/search-autocomplete.js for Vercel static deployment.
 *
 * Loads search suggestions from a local mock JSON file instead of
 * hitting the live `/search/suggest` API.  Form submissions are
 * redirected to the real GenScript search page.
 *
 * The mock data (`data/search-suggest.json`) mirrors the original
 * API's three-tier response: suggestHits, serviceHits, productHits.
 *
 * @module search-autocomplete
 * @deprecated Legacy jQuery UI Autocomplete code (circa 2016).
 */

'use strict';

jQuery(function ($) {

  // =========================================================================
  // Configuration
  // =========================================================================
  var SEARCH_SUGGEST_URL = 'data/search-suggest.json';
  var REAL_SEARCH_URL = 'https://www.genscript.com/search';

  // =========================================================================
  // Helpers
  // =========================================================================

  /**
   * Truncate a string to `maxLength`, appending "…" when truncated.
   */
  function truncateText(text, maxLength) {
    if (text.length > maxLength) {
      return text.substring(0, maxLength) + '…';
    }
    return text;
  }

  /**
   * Build the HTML for the autocomplete dropdown from the API response.
   *
   * Three categories are rendered (matching the original `/search/suggest`):
   *   1. serviceHits — navigate to service pages (title ≤100 chars)
   *   2. productHits — navigate to product pages (spuNo + shortName ≤90 chars)
   *   3. suggestHits — plain keyword completion (handled by autocomplete widget)
   *
   * @param {Object} data — parsed JSON from data/search-suggest.json
   * @returns {string} HTML string of <li> elements
   */
  function buildSuggestionDropdownHtml(data) {
    var html = '';
    var i, entry;

    // --- Service Hits ---
    if (data.serviceHits && data.serviceHits.length > 0) {
      for (i = 0; i < data.serviceHits.length; i++) {
        entry = data.serviceHits[i];
        html += '<li class="ui-menu-item" role="presentation">' +
          '<a class="ui-corner-all" style="line-height:24px" ' +
          'href="' + entry.url + '?position_no=' + (i + 1) + '&sensors=search+service+box" tabindex="-1">' +
          truncateText(entry.title, 100) +
          '</a></li>';
      }
      html += '<hr style="margin:5px 0">';
    }

    // --- Product Hits ---
    if (data.productHits && data.productHits.length > 0) {
      for (i = 0; i < data.productHits.length; i++) {
        entry = data.productHits[i];
        html += '<li class="ui-menu-item" role="presentation">' +
          '<a class="ui-corner-all" style="line-height:24px;font-size:12px" ' +
          'href="' + entry.url + '?position_no=' + (i + 1) + '&sensors=search+product+box" tabindex="-1">' +
          entry.spuNo + ' — ' + truncateText(entry.shortName, 90) +
          '</a></li>';
      }
      html += '<hr style="margin:5px 0">';
    }

    return html;
  }

  // =========================================================================
  // Autocomplete Initialization
  // =========================================================================

  $('.search-query').autocomplete({
    source: function (request, response) {
      $.getJSON(SEARCH_SUGGEST_URL, function (apiResponse) {
        var data = apiResponse.data;

        // Build navigation links for service/product hits
        var linksHtml = buildSuggestionDropdownHtml(data);
        $('.ui-autocomplete').html(linksHtml);

        // Register keyword suggestions with the autocomplete widget
        if (data.suggestHits && data.suggestHits.length > 0) {
          response($.map(data.suggestHits, function (item) {
            return { label: item, value: item };
          }));
        }
      }).fail(function () {
        console.warn('[Search] Could not load search suggestions.');
        response([]);
      });
    },
    minLength: 1,
    select: function (event, ui) {
      $('.search-query').val(ui.item.value);
    }
  });

  // On form submit, redirect to the real GenScript search
  $('.search-form').on('submit', function (e) {
    var query = $(this).find('.search-query').val().trim();
    if (query) {
      e.preventDefault();
      window.location.href = REAL_SEARCH_URL + '?q=' + encodeURIComponent(query);
    }
  });

});
