/**
 * GenScript Mega Menu — Hover-triggered | Absolute-positioned under nav
 * Desktop: hover to open | Mobile: click toggle accordion
 */
(function ($, window) {
  'use strict';

  var activePanel = null;
  var closeTimer = null;
  var DESKTOP = window.matchMedia('(min-width: 993px)');

  // ── Open a specific panel ──────────────────────────────────
  function openPanel(menuId) {
    clearTimeout(closeTimer);
    if (activePanel && activePanel !== menuId) {
      $('#' + activePanel).removeClass('open');
      $('.nav-item').removeClass('nav-hovered');
    }
    $('#' + menuId).addClass('open');
    $('.nav-item[data-mega="' + menuId + '"]').addClass('nav-hovered');
    // Activate first left-item
    $('#' + menuId).find('.mega-left-item').first().trigger('mouseenter');
    activePanel = menuId;
  }

  function closeAll() {
    $('.mega-panel').removeClass('open');
    $('.nav-item').removeClass('nav-hovered');
    activePanel = null;
  }

  // ── Desktop: hover open ────────────────────────────────────
  $(document).on('mouseenter', '.nav-item[data-mega]', function () {
    if (!DESKTOP.matches) return;
    openPanel($(this).data('mega'));
  });

  // ── Desktop: delayed close on mouseleave ───────────────────
  $(document).on('mouseleave', '.nav-item[data-mega]', function () {
    if (!DESKTOP.matches) return;
    closeTimer = setTimeout(function () {
      if (!$('.mega-panel:hover').length && !$('.nav-item[data-mega]:hover').length) {
        closeAll();
      }
    }, 220);
  });

  $(document).on('mouseenter', '.mega-panel', function () {
    clearTimeout(closeTimer);
  });

  $(document).on('mouseleave', '.mega-panel', function () {
    if (!DESKTOP.matches) return;
    closeTimer = setTimeout(function () {
      if (!$('.nav-item[data-mega]:hover').length) {
        closeAll();
      }
    }, 200);
  });

  // ── Mobile: click toggle ───────────────────────────────────
  $(document).on('click', '.nav-item[data-mega]', function (e) {
    if (DESKTOP.matches) return;
    e.preventDefault();
    var id = $(this).data('mega');
    if (activePanel === id) { closeAll(); return; }
    openPanel(id);
  });

  // ── ESC key ────────────────────────────────────────────────
  $(document).on('keydown', function (e) { if (e.keyCode === 27) closeAll(); });

  // ── Click outside to close ─────────────────────────────────
  $(document).on('click', function (e) {
    if (!DESKTOP.matches) return;
    if (!$(e.target).closest('.mega-panel, .nav-item[data-mega]').length) {
      closeAll();
    }
  });

  // ── Left-tab switching ─────────────────────────────────────
  $(document).on('mouseenter click', '.mega-left-item', function () {
    var $this = $(this);
    var $panel = $this.closest('.mega-panel');
    var mid = $this.data('mid');
    var right = $this.data('right');

    $panel.find('.mega-left-item').removeClass('active');
    $this.addClass('active');

    if (mid !== undefined) {
      $panel.find('.mega-mid-group').removeClass('active');
      $panel.find('.mega-mid-group[data-group="' + mid + '"]').addClass('active');
    }
    if (right !== undefined) {
      $panel.find('.mega-right-group').hide();
      $panel.find('.mega-right-group[data-group="' + right + '"]').show();
    }
  });

  // ── Resize cleanup ─────────────────────────────────────────
  DESKTOP.addEventListener('change', function () { closeAll(); });

})(jQuery, window);
