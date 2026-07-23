function getURLVar(key) {
  var value = [];

  var query = String(document.location).split('?');

  if (query[1]) {
    var part = query[1].split('&');

    for (i = 0; i < part.length; i++) {
      var data = part[i].split('=');

      if (data[0] && data[1]) {
        value[data[0]] = data[1];
      }
    }

    if (value[key]) {
      return value[key];
    } else {
      return '';
    }
  }
}

var show_load = function() {
  layer.load(2, {shade: [0.1,'#fff'] });
}

var hide_load = function() {
  layer.closeAll('loading');
}

var cart_ajax_load_html = function() {
  $('.cart-wrapper').load('index.php?route=common/cart/info');
}

// ajax 默认全局设置
$.ajaxSetup({
  cache: false,
  beforeSend: function() { show_load(); },
  complete: function() { hide_load(); },
  error: function(xhr, ajaxOptions, thrownError) {
    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
  }
});

$(document).ready(function() {

  $('.scroll-top').on('click', function(event) {
    event.preventDefault();
    $('html, body').animate({ scrollTop: 0} , 'fast');
  });

  $(document).on('click', '.mobile-nav-icon, .mobile-search', function(event) {
    $('.side-menu').fadeIn(0).children('.side-inner').addClass('active');
  });

  $(document).on('click', '.side-menu', function(e) {
    if ( $(e.target).closest('.side-inner').length === 0 ) {
      $('.side-menu .side-inner').removeClass('active');
      setTimeout("$('.side-menu').fadeOut(50)", 220);
    }
  });

  $('.side-menu li .toggle-button').click(function(event) {
    $(this).parent().siblings().removeClass('open active').children('.dropdown-menu').slideUp();
    $(this).parent().toggleClass('active').find('.dropdown-menu').slideToggle("fast");
  });

  // Highlight any found errors
  $('.text-danger').each(function() {
    var element = $(this).parent().parent();

    if (element.hasClass('form-group')) {
      element.addClass('has-error');
    }
  });

  // Currency
  $('#form-currency .currency-select').on('click', function(e) {
    e.preventDefault();

    $('#form-currency input[name=\'code\']').val($(this).attr('name'));

    $('#form-currency').submit();
  });

  // Language
  $('#form-language .language-select').on('click', function(e) {
    e.preventDefault();

    $('#form-language input[name=\'code\']').val($(this).attr('name'));

    $('#form-language').submit();
  });

  /* Search */
  $('#search input[name=\'search\']').parent().find('button').on('click', function() {
    var url = $('base').attr('href') + 'index.php?route=product/search';

    var value = $(this).siblings('input').val();

    if (value) {
      url += '&search=' + encodeURIComponent(value);
    }

    location = url;
  });

  $('#search input[name=\'search\']').on('keydown', function(e) {
    if (e.keyCode == 13) {
      $(this).siblings('button').trigger('click');
    }
  });

  // Menu
  $('#menu .dropdown-menu').each(function() {
    var menu = $('#menu').offset();
    var dropdown = $(this).parent().offset();

    var i = (dropdown.left + $(this).outerWidth()) - (menu.left + $('#menu').outerWidth());

    if (i > 0) {
      $(this).css('margin-left', '-' + (i + 10) + 'px');
    }
  });

  // Checkout
  $(document).on('keydown', '#collapse-checkout-option input[name=\'email\'], #collapse-checkout-option input[name=\'password\']', function(e) {
    if (e.keyCode == 13) {
      $('#collapse-checkout-option #button-login').trigger('click');
    }
  });

  // tooltips on hover
  $('[data-toggle=\'tooltip\']').tooltip({container: 'body'});

  // Makes tooltips work on ajax generated content
  $(document).ajaxStop(function() {
    $('[data-toggle=\'tooltip\']').tooltip({container: 'body'});
  });

  $(document).on('click', '#review .pagination a', function(e) {
    e.preventDefault();
    $('#review').load(this.href);
  });

  $('.more-review').on('click', function(e) {
    $("html, body").stop().animate({ scrollTop: $('.nav-tabs').offset().top }, 400);
    $('a[href=\'#tab-review\']').trigger('click');
  })
});

// Cart add remove functions
var cart = {
  'add': function(product_id, quantity, el) {
    var $btn = el ? $(el) : null;
    $.ajax({
      url: 'index.php?route=checkout/cart/add',
      type: 'post',
      data: 'product_id=' + product_id + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
      dataType: 'json',
      beforeSend: function() {},  // suppress global loading spinner - snappy add (matches wishlist.toggle)
      complete: function() {},
      success: function(json) {
        $('.alert-dismissible, .text-danger').remove();

        if (json['redirect']) {
          location = json['redirect'];
        }

        if (json['success']) {
          // Need to set timeout otherwise it wont update the total
          setTimeout(function () {
            $('#cart > button #cart-total').html(json['total']);
          }, 100);

          // Success popup suppressed per request - count badge + button checkmark
          // are enough feedback (matches the wishlist.toggle silent behavior).
          // showAlert('cart', json['success']);

          $('#cart > ul').load('index.php?route=common/cart/info ul li');

          // Header cart count badge (like the wishlist heart badge).
          if (typeof json['count'] !== 'undefined') {
            cart.setCount(json['count']);
          }

          // Swap the clicked card button icon to a checkmark, then revert.
          if ($btn && $btn.length) {
            cart.markAdded($btn);
          }
        }
      }
    });
  },
  // Update the header cart count badge.
  'setCount': function(count) {
    var $c = $('#cart-count');
    if (!$c.length) {
      $('.header-cart-btn').append('<span class="header-cart-count" id="cart-count"></span>');
      $c = $('#cart-count');
    }
    $c.text(count);
    if (count > 0) { $c.show(); } else { $c.hide(); }
  },
  // Temporarily swap a card add-button's cart icon to a checkmark to confirm the add.
  'markAdded': function($btn) {
    if (!$btn || !$btn.length) return;
    if ($btn.data('cart-added-timer')) {
      clearTimeout($btn.data('cart-added-timer'));
      $btn.find('i.fa-check').first().removeClass('fa-check').addClass('fa-cart-shopping');
    }
    $btn.addClass('is-added');
    var $icon = $btn.find('i.fa-cart-shopping').first();
    if ($icon.length) {
      $icon.removeClass('fa-cart-shopping').addClass('fa-check');
    }
    var t = setTimeout(function() {
      $btn.removeClass('is-added');
      $btn.find('i.fa-check').first().removeClass('fa-check').addClass('fa-cart-shopping');
    }, 1500);
    $btn.data('cart-added-timer', t);
  },
  'remove': function(key) {
    $.ajax({
      url: 'index.php?route=checkout/cart/remove',
      type: 'post',
      data: 'key=' + key,
      dataType: 'json',
      success: function(json) {
        // Need to set timeout otherwise it wont update the total
        setTimeout(function () {
          $('#cart > button #cart-total').html(json['total']);
        }, 100);

        if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
          location = 'index.php?route=checkout/cart';
        } else {
          $('#cart > ul').load('index.php?route=common/cart/info ul li');
        }
      }
    });
  }
}

var voucher = {
  'remove': function(key) {
    $.ajax({
      url: 'index.php?route=checkout/cart/remove',
      type: 'post',
      data: 'key=' + key,
      dataType: 'json',
      success: function(json) {
        // Need to set timeout otherwise it wont update the total
        setTimeout(function () {
          $('#cart > button #cart-total').html(json['total']);
        }, 100);

        if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
          location = 'index.php?route=checkout/cart';
        } else {
          $('#cart > ul').load('index.php?route=common/cart/info ul li');
        }
      }
    });
  }
}

var wishlist = {
  'add': function(product_id) {
    $.ajax({
      url: 'index.php?route=account/wishlist/add',
      type: 'post',
      data: 'product_id=' + product_id,
      dataType: 'json',
      success: function(json) {
        $('.alert-dismissible').remove();

        if (json['redirect']) {
          location = json['redirect'];
        }

        if (json['success']) {
          showAlert('wishlist', json['success']);
        }

        $('#wishlist-total span').html(json['total']);
        $('#wishlist-total').attr('title', json['total']);
      }
    });
  },
  // Toggle a product in/out of the wishlist. `el` is the heart button.
  'toggle': function(product_id, el) {
    var $btn = $(el);
    $.ajax({
      url: 'index.php?route=account/wishlist/toggle',
      type: 'post',
      data: 'product_id=' + product_id,
      dataType: 'json',
      beforeSend: function() {},  // suppress global loading overlay for a snappy heart click
      complete: function() {},
      success: function(json) {
        if (json['redirect']) {
          location = json['redirect'];
          return;
        }
        if (typeof json['in_wishlist'] !== 'undefined') {
          wishlist.setHeart($btn, json['in_wishlist']);
          wishlist.setCount(json['total']);

          // Success popup suppressed per request - heart fill + count badge are enough feedback on add.

          // On the wishlist page, removing an item fades its card out.
          if (json['action'] === 'removed' && $btn.hasClass('wl-remove')) {
            var $card = $btn.closest('.wl-item');
            if ($card.length) {
              $card.fadeOut(220, function() {
                $card.remove();
                if (!$('.wl-item').length) {
                  $('.wl-grid').remove();
                  $('.wl-empty').show();
                }
              });
            }
          }
        }
      }
    });
  },
  // Set the filled/outline state of a heart button.
  'setHeart': function($btn, inWishlist) {
    if (!$btn || !$btn.length) return;
    var $icon = $btn.find('i');
    if (inWishlist) {
      $btn.addClass('is-active');
      $icon.removeClass('fa-regular').addClass('fa-solid');
    } else {
      $btn.removeClass('is-active');
      $icon.removeClass('fa-solid').addClass('fa-regular');
    }
  },
  // Update the header heart count badge.
  'setCount': function(total) {
    var $c = $('#wishlist-count');
    if (!$c.length) {
      $('.header-wishlist-btn').append('<span class="header-wish-count" id="wishlist-count"></span>');
      $c = $('#wishlist-count');
    }
    $c.text(total);
    if (total > 0) { $c.show(); } else { $c.hide(); }
  },
  // Fill every heart whose product_id is in `ids`.
  'markHearts': function(ids) {
    var set = {};
    if (ids && ids.length) {
      for (var i = 0; i < ids.length; i++) {
        set[String(ids[i])] = true;
      }
    }
    $('.pcard-wishlist').each(function() {
      var pid = String($(this).data('product-id'));
      wishlist.setHeart($(this), !!set[pid]);
    });
  },
  // On load, fetch the user's wishlist ids and mark matching hearts.
  'init': function() {
    $.ajax({
      url: 'index.php?route=account/wishlist/ids',
      type: 'get',
      dataType: 'json',
      beforeSend: function() {},
      complete: function() {},
      success: function(json) {
        if (json && json['ids']) {
          wishlist.markHearts(json['ids']);
        }
      }
    });
  }
}

$(document).ready(function() {
  if (typeof wishlist !== 'undefined' && wishlist.init) {
    wishlist.init();
  }
});

var compare = {
  'add': function(product_id) {
    $.ajax({
      url: 'index.php?route=product/compare/add',
      type: 'post',
      data: 'product_id=' + product_id,
      dataType: 'json',
      success: function(json) {
        $('.alert-dismissible').remove();

        if (json['success']) {
          showAlert('compare', json['success']);

          $('#compare-total').html(json['total']);
        }
      }
    });
  }
}

/* Agree to Terms */
$(document).on('click', '.agree', function(e) {
  e.preventDefault();

  var $element = $(this);

  layer.open({
    type: 2,
    title: $element.text(),
    skin: 'agree-to-terms',
    content: [$element.attr('href')],
  });
});

// Autocomplete */
(function($) {
  $.fn.autocomplete = function(option) {
    return this.each(function() {
      this.timer = null;
      this.items = new Array();

      $.extend(this, option);

      $(this).attr('autocomplete', 'off');

      // Focus
      $(this).on('focus', function() {
        this.request();
      });

      // Blur
      $(this).on('blur', function() {
        setTimeout(function(object) {
          object.hide();
        }, 200, this);
      });

      // Keydown
      $(this).on('keydown', function(event) {
        switch(event.keyCode) {
          case 27: // escape
            this.hide();
            break;
          default:
            this.request();
            break;
        }
      });

      // Click
      this.click = function(event) {
        event.preventDefault();

        value = $(event.target).parent().attr('data-value');

        if (value && this.items[value]) {
          this.select(this.items[value]);
        }
      }

      // Show
      this.show = function() {
        var pos = $(this).position();

        $(this).siblings('ul.dropdown-menu').css({
          top: pos.top + $(this).outerHeight(),
          left: pos.left
        });

        $(this).siblings('ul.dropdown-menu').show();
      }

      // Hide
      this.hide = function() {
        $(this).siblings('ul.dropdown-menu').hide();
      }

      // Request
      this.request = function() {
        clearTimeout(this.timer);

        this.timer = setTimeout(function(object) {
          object.source($(object).val(), $.proxy(object.response, object));
        }, 200, this);
      }

      // Response
      this.response = function(json) {
        html = '';

        if (json.length) {
          for (i = 0; i < json.length; i++) {
            this.items[json[i]['value']] = json[i];
          }

          for (i = 0; i < json.length; i++) {
            if (!json[i]['category']) {
              html += '<li data-value="' + json[i]['value'] + '"><a href="#">' + json[i]['label'] + '</a></li>';
            }
          }

          // Get all the ones with a categories
          var category = new Array();

          for (i = 0; i < json.length; i++) {
            if (json[i]['category']) {
              if (!category[json[i]['category']]) {
                category[json[i]['category']] = new Array();
                category[json[i]['category']]['name'] = json[i]['category'];
                category[json[i]['category']]['item'] = new Array();
              }

              category[json[i]['category']]['item'].push(json[i]);
            }
          }

          for (i in category) {
            html += '<li class="dropdown-header">' + category[i]['name'] + '</li>';

            for (j = 0; j < category[i]['item'].length; j++) {
              html += '<li data-value="' + category[i]['item'][j]['value'] + '"><a href="#">&nbsp;&nbsp;&nbsp;' + category[i]['item'][j]['label'] + '</a></li>';
            }
          }
        }

        if (html) {
          this.show();
        } else {
          this.hide();
        }

        $(this).siblings('ul.dropdown-menu').html(html);
      }

      $(this).after('<ul class="dropdown-menu"></ul>');
      $(this).siblings('ul.dropdown-menu').delegate('a', 'click', $.proxy(this.click, this));

    });
  }
})(window.jQuery);

// 商品详情加购物车
;(function($) {
  var ProductInfoToCart = function(element, options) {
    var defaults = {
      data: [],
    };

    this.element = element;
    this.settings = $.extend({}, defaults, options);
    this.init();
  };

  ProductInfoToCart.prototype = {
    init: function() {
      var self = this, settings = this.settings;

      $(this.element).on('click', function(e) {
        self.ajax(settings);
      })
    },
    ajax: function(settings) {
      if ( !settings.data.length ) return;
      $.ajax({
        url: 'index.php?route=checkout/cart/add',
        type: 'post',
        data: $(settings.data.join(',')),
        dataType: 'json',
        success: function(json) {
          $('.alert-dismissible, .text-danger').remove();
          $('.form-group').removeClass('has-error');

          if (json['error']) {
            if (json['error']['option']) {
              for (i in json['error']['option']) {
                var element = $('#input-option' + i.replace('_', '-'));

                if (element.parent().hasClass('input-group')) {
                  element.parent().after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
                } else {
                  element.after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
                }
              }
            }

            $('.text-danger').parent().addClass('has-error');
          }

          if (json['success']) {
            showAlert('cart', json['success']);
            cart_ajax_load_html();
          }
        },
      });
    },
  }

  $.fn.productInfoToCart = function (options) {
    this.each(function(index, el) {
      new ProductInfoToCart(this, options);
    });

    return this;
  };
})(jQuery);

