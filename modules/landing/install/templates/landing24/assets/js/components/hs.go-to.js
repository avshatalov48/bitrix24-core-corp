/**
 * Go To wrapper.
 *
 * @author Htmlstream
 * @version 1.0
 *
 */
;(function ($) {
  'use strict';
  $.HSCore.components.HSGoTo = {
    /**
     *
     *
     * @var Object _baseConfig
     */
    _baseConfig: {},

    /**
     *
     *
     * @var jQuery pageCollection
     */
    pageCollection: $(),

    /**
     * Initialization of Go To wrapper.
     *
     * @param String selector (optional)
     * @param Object config (optional)
     *
     * @return jQuery pageCollection - collection of initialized items.
     */

    init: function (selector, config) {
      this.collection = selector && $(selector).length ? $(selector) : $();
      if (!$(selector).length) return;

      this.config = config && $.isPlainObject(config) ?
        $.extend({}, this._baseConfig, config) : this._baseConfig;

      this.config.itemSelector = selector;

      this.initGoTo();

      return this.pageCollection;
    },

    initGoTo: function () {
      //Variables
      var $self = this,
        collection = $self.pageCollection;

      //Actions
      this.collection.each(function (i, el) {
        //Variables
        var $this = $(el),
          $target = $this.data('target'),
          type = $this.data('type'),
          showEffect = $this.data('show-effect'),
          hideEffect = $this.data('hide-effect'),
          position = JSON.parse(el.getAttribute('data-position')),
          compensation = $($this.data('compensation')).outerHeight(),
          offsetTop = $this.data('offset-top');

        $this.addClass('animated').css({
          'display': 'inline-block',
          'position': type,
          'opacity': 0
        });

        if (type == 'fixed' || type == 'absolute') {
          // setTimeout(function () {
          //   $this.hide();
          // }, 400);

          $this.css(position);
        }

        $this.on('click', function (e) {
          e.preventDefault();

          $('html, body').stop().animate({
            'scrollTop': $target ? $($target).offset().top - compensation : 0
          }, 800);
        });

        if (!$this.data('offset-top') && !$this.hasClass('js-animation-was-fired')) {
          if ($this.offset().top <= $(window).height()) {
            $this.show();

            setTimeout(function() {
              $this.addClass('js-animation-was-fired ' + showEffect).css({
                'opacity': ''
              });
            });
          }
        }

        $(window).on('scroll', function () {
          if ($this.data('offset-top')) {
            if ($(window).scrollTop() >= offsetTop && !$this.hasClass('js-animation-was-fired')) {
              $this.show();

              setTimeout(function() {
                $this.addClass('js-animation-was-fired ' + showEffect).css({
                  'opacity': ''
                });
              });
            } else if ($(window).scrollTop() <= offsetTop && $this.hasClass('js-animation-was-fired')) {
              $this.removeClass('js-animation-was-fired ' + showEffect);

              setTimeout(function() {
                $this.addClass(hideEffect).css({
                  'opacity': 0
                });
              }, 100);

              setTimeout(function() {
                $this.removeClass(hideEffect).hide();
              }, 400);
            }
          } else {
            var thisOffsetTop = $this.offset().top;

            if (!$this.hasClass('js-animation-was-fired')) {
              if ($(window).scrollTop() >= thisOffsetTop - $(window).height()) {
                $this.show();

                setTimeout(function() {
                  $this.addClass('js-animation-was-fired ' + showEffect).css({
                    'opacity': ''
                  });
                });
              }
            }
          }
        });

        $(window).trigger('scroll');

        //Actions
        collection = collection.add($this);
      });
    }
  };
})(jQuery);
