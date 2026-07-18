/**
 * GenScript Homepage Initialization (Static Deploy)
 * ==================================================
 * Adapted from modules/homepage-init.js for Vercel static deployment.
 *
 * Initializes:
 *   - Sticky header on scroll
 *   - Banner Swiper carousel (6 slides, autoplay 10s)
 *   - Testimonial Swiper carousel (12 slides, 2-up)
 *   - Events Owl Carousel (3 cards)
 *   - Scroll-to-top button
 *   - Dynamic News & Blogs loading (from data/learncenter.json)
 *
 * Uses CDN-loaded Swiper 3.4.2 jQuery + Owl Carousel 2.3.4.
 * All initializations are wrapped in try-catch for graceful degradation.
 *
 * @module homepage-init
 */

'use strict';

(function ($, window, document) {

  // =========================================================================
  // Configuration
  // =========================================================================
  var CONFIG = {
    LEARNCENTER_URL: 'data/learncenter.json',
    BANNER_AUTOPLAY_DELAY: 10000,
    BANNER_ANIMATION: 'slide',
    TESTIMONIAL_SLIDES_PER_VIEW: 2,
    TESTIMONIAL_SPACING: 50,
  };

  // =========================================================================
  // 1. Sticky Header (DISABLED — header is position:fixed in redesign.css)
  // =========================================================================
  // The old initStickyHeader() set background:#fff on scroll and
  // position:relative at scrollTop=0, breaking the dark navy theme.
  // Since redesign.css already sets #header { position:fixed; background:#000a46 },
  // no JS-based scroll management is needed.
  function initStickyHeader() {
    // Intentionally empty — header styling is handled purely by CSS.
    return;
  }

  // =========================================================================
  // 2. Banner Swiper (Hero Carousel)
  // =========================================================================
  function initBannerSwiper() {
    try {
      var bannerSwiper = new Swiper('.banner-container', {
        slidesPerView: 1,
        effect: CONFIG.BANNER_ANIMATION,
        autoplay: CONFIG.BANNER_AUTOPLAY_DELAY,
        autoplayDisableOnInteraction: false,
        pagination: '.swiper-pagination',
        paginationClickable: true,
        nextButton: '.swiper-button-next',
        prevButton: '.swiper-button-prev',
        loop: true,
        speed: 600,
      });
      console.log('[Init] Banner Swiper initialized');
      return bannerSwiper;
    } catch (e) {
      console.warn('[Init] Banner Swiper failed — Swiper.js not loaded:', e.message);
      return null;
    }
  }

  // =========================================================================
  // 3. Testimonial Swiper (Customer Stories)
  // =========================================================================
  function initTestimonialSwiper() {
    try {
      var $container = $('.view .swiper-container');
      if ($container.length === 0) {
        console.warn('[Init] Testimonial swiper container not found');
        return null;
      }

      var testimonialSwiper = new Swiper($container[0], {
        slidesPerView: CONFIG.TESTIMONIAL_SLIDES_PER_VIEW,
        spaceBetween: CONFIG.TESTIMONIAL_SPACING,
        pagination: '.swiper-pagination',
        paginationClickable: true,
        loop: true,
        speed: 500,
        breakpoints: {
          768: { slidesPerView: 1, spaceBetween: 20 },
          1024: { slidesPerView: 2, spaceBetween: 50 },
        },
      });
      console.log('[Init] Testimonial Swiper initialized');
      return testimonialSwiper;
    } catch (e) {
      console.warn('[Init] Testimonial Swiper failed:', e.message);
      return null;
    }
  }

  // =========================================================================
  // 4. Events Owl Carousel
  // =========================================================================
  function initEventsOwlCarousel() {
    try {
      var $owl = $('.owl-carousel');
      if ($owl.length === 0) {
        console.warn('[Init] Owl Carousel container not found');
        return null;
      }

      var owl = $owl.owlCarousel({
        items: 3,
        loop: true,
        margin: 20,
        nav: true,
        dots: true,
        autoplay: true,
        autoplayTimeout: 5000,
        autoplayHoverPause: true,
        responsive: {
          0: { items: 1 },
          768: { items: 2 },
          1024: { items: 3 },
        },
      });

      // Wire custom nav buttons
      $('.owl-prev-custom').on('click', function () {
        $owl.trigger('prev.owl.carousel');
      });
      $('.owl-next-custom').on('click', function () {
        $owl.trigger('next.owl.carousel');
      });

      console.log('[Init] Events Owl Carousel initialized');
      return owl;
    } catch (e) {
      console.warn('[Init] Owl Carousel failed:', e.message);
      return null;
    }
  }

  // =========================================================================
  // 5. Scroll-to-Top Button
  // =========================================================================
  function initScrollUp() {
    try {
      if ($.fn.scrollUp) {
        $.scrollUp({
          scrollName: 'scrollUp',
          scrollDistance: 300,
          scrollFrom: 'top',
          scrollSpeed: 300,
          easingType: 'linear',
          animation: 'fade',
          scrollText: '↑',
          zIndex: 999,
        });
        console.log('[Init] Scroll-to-top initialized');
      }
    } catch (e) {
      console.warn('[Init] ScrollUp failed:', e.message);
    }
  }

  // =========================================================================
  // 6. Dynamic Content: News & Blogs
  // =========================================================================
  function loadLearncenterContent() {
    var $container = $('.learncenter-container');
    if ($container.length === 0) return;

    $.ajax({
      url: CONFIG.LEARNCENTER_URL,
      type: 'GET',
      dataType: 'json',
      success: function (response) {
        if (response && response.data) {
          $container.html(response.data);
          console.log('[Init] Learncenter content loaded');
        } else {
          $container.html('<p class="text-center text-muted">No content available.</p>');
        }
      },
      error: function (xhr, status, error) {
        console.warn('[Init] Learncenter content failed:', error);
        $container.html(
          '<p class="text-center text-muted">' +
          'Content unavailable in demo mode.<br>' +
          '<a href="https://www.genscript.com/news-blogs.html">Visit GenScript News &amp; Blogs →</a>' +
          '</p>'
        );
      },
    });
  }

  // =========================================================================
  // 7. Entry Point
  // =========================================================================
  $(document).ready(function () {
    console.log('[GenScript Demo] Homepage initializing…');

    initStickyHeader();
    initBannerSwiper();
    initTestimonialSwiper();
    initEventsOwlCarousel();
    // initScrollUp();  // disabled — footer.twig provides its own back-to-top button
    loadLearncenterContent();

    // Mobile menu (vanilla JS — self-contained, no jQuery dependency)
    initMobileMenu();

    console.log('[GenScript Demo] Homepage ready ✓');
  });

  // =========================================================================
  // 7. Mobile Drawer Menu  (vanilla JS, ≤1024px)
  // =========================================================================
  function initMobileMenu() {
    var menuToggle = document.getElementById('menu-toggle');
    var drawerClose = document.getElementById('drawer-close');
    var drawer     = document.getElementById('mobile-menu');
    var overlay    = document.getElementById('drawer-overlay');
    var body       = document.body;

    if (!menuToggle || !drawerClose || !drawer || !overlay) return;

    /**
     * Open the drawer: slide in from right, show dark overlay, lock scroll.
     */
    function openDrawer() {
      drawer.classList.add('active');
      overlay.classList.add('active');
      body.style.overflow = 'hidden';
    }

    /**
     * Close the drawer: slide out, hide overlay, restore scroll.
     */
    function closeDrawer() {
      drawer.classList.remove('active');
      overlay.classList.remove('active');
      body.style.overflow = '';
    }

    // Hamburger button → open drawer
    menuToggle.addEventListener('click', openDrawer);

    // × close button → close drawer
    drawerClose.addEventListener('click', closeDrawer);

    // Click dark overlay → close drawer
    overlay.addEventListener('click', closeDrawer);

    // Press Escape key → close drawer
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && drawer.classList.contains('active')) {
        closeDrawer();
      }
    });

    // Search icon → scroll to search input and focus it
    var searchIcon = document.querySelector('.mob-icon-search');
    if (searchIcon) {
      searchIcon.addEventListener('click', function () {
        var searchInput = document.querySelector('.header-search input');
        if (searchInput) {
          searchInput.scrollIntoView({ behavior: 'smooth' });
          setTimeout(function () { searchInput.focus(); }, 400);
        }
      });
    }

    console.log('[Init] Mobile menu ready (drawer + overlay)');
  }

})(jQuery, window, document);
