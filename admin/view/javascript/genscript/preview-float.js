/* ==========================================================================
 *  preview-float.js - shared floating real-time preview window behavior
 *  (drag / minimize / close / reopen). Used by admin 商品卡片管理 &
 *  商品详情页管理. Each page supplies the chrome markup with ids:
 *    #pf-window, #pf-header, #pf-min, #pf-close, #pf-fab
 *  and keeps its own render() that paints the inner canvas
 *  (#pcard-preview-frame / #pdp-preview-frame). Position + collapsed state
 *  persist across reloads via sessionStorage (keyed per page).
 * ========================================================================== */
(function () {
  function init() {
    var win = document.getElementById('pf-window');
    if (!win) return;                 // not a preview-float page
    var header = document.getElementById('pf-header');
    var minBtn = document.getElementById('pf-min');
    var closeBtn = document.getElementById('pf-close');
    var fab = document.getElementById('pf-fab');
    if (!header || !minBtn || !closeBtn || !fab) return;

    var storeKey = 'pf:' + window.location.pathname + window.location.search;

    // ---- restore persisted state ----
    try {
      var saved = JSON.parse(sessionStorage.getItem(storeKey) || '{}');
      if (saved.left != null && saved.top != null) {
        win.style.left = saved.left + 'px';
        win.style.top = saved.top + 'px';
        win.style.right = 'auto';
      }
      if (saved.collapsed) { win.classList.add('pf-collapsed'); }
      if (saved.hidden) {
        win.classList.add('pf-hidden');
        fab.classList.add('pf-show');
      }
    } catch (_) {}

    function persist() {
      var rect = win.getBoundingClientRect();
      try {
        sessionStorage.setItem(storeKey, JSON.stringify({
          left: Math.round(rect.left),
          top: Math.round(rect.top),
          collapsed: win.classList.contains('pf-collapsed'),
          hidden: win.classList.contains('pf-hidden')
        }));
      } catch (_) {}
    }

    // ---- minimize / restore ----
    minBtn.addEventListener('click', function () {
      win.classList.toggle('pf-collapsed');
      persist();
    });

    // ---- close / reopen ----
    closeBtn.addEventListener('click', function () {
      win.classList.add('pf-hidden');
      fab.classList.add('pf-show');
      persist();
    });
    fab.addEventListener('click', function () {
      win.classList.remove('pf-hidden');
      fab.classList.remove('pf-show');
      persist();
    });

    // ---- drag (pointer events; works for mouse + touch) ----
    var dragging = false, sx = 0, sy = 0, ox = 0, oy = 0;

    header.addEventListener('pointerdown', function (e) {
      // ignore drags starting on a header button
      if (e.target.closest('.pf-btn')) return;
      dragging = true;
      win.classList.add('pf-dragging');
      var rect = win.getBoundingClientRect();
      // pin to left/top so movement is deterministic
      win.style.left = rect.left + 'px';
      win.style.top = rect.top + 'px';
      win.style.right = 'auto';
      sx = e.clientX; sy = e.clientY;
      ox = rect.left; oy = rect.top;
      header.setPointerCapture(e.pointerId);
      e.preventDefault();
    });
    header.addEventListener('pointermove', function (e) {
      if (!dragging) return;
      var nx = ox + (e.clientX - sx);
      var ny = oy + (e.clientY - sy);
      // constrain to viewport (keep the header bar reachable)
      var maxX = window.innerWidth - 80;
      var maxY = window.innerHeight - 40;
      nx = Math.max(0, Math.min(nx, maxX));
      ny = Math.max(0, Math.min(ny, maxY));
      win.style.left = nx + 'px';
      win.style.top = ny + 'px';
    });
    function endDrag(e) {
      if (!dragging) return;
      dragging = false;
      win.classList.remove('pf-dragging');
      if (e && header.releasePointerCapture && e.pointerId != null) {
        try { header.releasePointerCapture(e.pointerId); } catch (_) {}
      }
      persist();
    }
    header.addEventListener('pointerup', endDrag);
    header.addEventListener('pointercancel', endDrag);

    // keep the window inside the viewport on resize
    window.addEventListener('resize', function () {
      if (win.classList.contains('pf-hidden')) return;
      var rect = win.getBoundingClientRect();
      if (rect.left + 80 > window.innerWidth || rect.top + 40 > window.innerHeight || rect.left < 0 || rect.top < 0) {
        // snapped off-screen -> reset to default top-right
        win.style.left = 'auto';
        win.style.top = '64px';
        win.style.right = '16px';
        persist();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
