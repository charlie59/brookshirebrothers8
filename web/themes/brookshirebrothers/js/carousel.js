(function ($, Drupal) {
  "use strict";
  Drupal.behaviors.brookshireCarousel = {
    attach: function (context, settings) {

      // cycle scroll gallery init
      function initCycleCarousel() {
        let Carousel = $('div.carousel').once("brookshireCarousel").scrollAbsoluteGallery({
          mask: 'div.mask',
          slider: 'div.slideset',
          slides: 'div.slide',
          btnPrev: 'a.btn-prev',
          btnNext: 'a.btn-next',
          pagerLinks: '.pagination li',
          pauseOnHover: true,
          maskAutoSize: true,
          autoRotation: true,
          switchTime: 5000,
          animSpeed: 500
        });
      }

      function ScrollAbsoluteGallery(options) {
        this.options = $.extend({
          activeClass: 'active',
          mask: 'div.slides-mask',
          slider: '>ul',
          slides: '>li',
          btnPrev: '.btn-prev',
          btnNext: '.btn-next',
          pagerLinks: 'ul.pager > li',
          generatePagination: false,
          pagerList: '<ul>',
          pagerListItem: '<li><a href="#"></a></li>',
          pagerListItemText: 'a',
          galleryReadyClass: 'gallery-js-ready',
          currentNumber: 'span.current-num',
          totalNumber: 'span.total-num',
          maskAutoSize: false,
          autoRotation: false,
          pauseOnHover: false,
          stretchSlideToMask: false,
          switchTime: 3000,
          animSpeed: 500,
          handleTouch: true,
          swipeThreshold: 15,
          vertical: false
        }, options);
        this.init();
      }

      ScrollAbsoluteGallery.prototype = {
        init: function () {
          if (this.options.holder) {
            this.findElements();
            this.attachEvents();
            this.makeCallback('onInit', this);
          }
        },
        findElements: function () {
          // find structure elements
          this.holder = $(this.options.holder).addClass(this.options.galleryReadyClass);
          this.mask = this.holder.find(this.options.mask);
          this.slider = this.mask.find(this.options.slider);
          this.slides = this.slider.find(this.options.slides);
          this.btnPrev = this.holder.find(this.options.btnPrev);
          this.btnNext = this.holder.find(this.options.btnNext);

          // slide count display
          this.currentNumber = this.holder.find(this.options.currentNumber);
          this.totalNumber = this.holder.find(this.options.totalNumber);

          // create gallery pagination
          if (typeof this.options.generatePagination === 'string') {
            this.pagerLinks = this.buildPagination();
          } else {
            this.pagerLinks = this.holder.find(this.options.pagerLinks);
          }

          // define index variables
          this.sizeProperty = this.options.vertical ? 'height' : 'width';
          this.positionProperty = this.options.vertical ? 'top' : 'left';
          this.animProperty = this.options.vertical ? 'marginTop' : 'marginLeft';

          this.slideSize = this.slides[this.sizeProperty]();
          this.currentIndex = 0;
          this.prevIndex = 0;

          // reposition elements
          this.options.maskAutoSize = this.options.vertical ? false : this.options.maskAutoSize;
          if (this.options.vertical) {
            this.mask.css({
              height: this.slides.innerHeight()
            });
          }
          if (this.options.maskAutoSize) {
            this.mask.css({
              height: this.slider.height()
            });
          }
          this.slider.css({
            position: 'relative',
            height: this.options.vertical ? this.slideSize * this.slides.length : '100%'
          });
          this.slides.css({
            position: 'absolute'
          }).css(this.positionProperty, -9999).eq(this.currentIndex).css(this.positionProperty, 0);
          this.refreshState();
        },

        buildPagination: function () {
          var pagerLinks = $();
          if (!this.pagerHolder) {
            this.pagerHolder = this.holder.find(this.options.generatePagination);
          }
          if (this.pagerHolder.length) {
            this.pagerHolder.empty();
            this.pagerList = $(this.options.pagerList).appendTo(this.pagerHolder);
            for (var i = 0; i < this.slides.length; i++) {
              $(this.options.pagerListItem).appendTo(this.pagerList).find(this.options.pagerListItemText).text(i + 1);
            }
            pagerLinks = this.pagerList.children();
          }
          return pagerLinks;
        },
        attachEvents: function () {
          // attach handlers
          var self = this;
          if (this.btnPrev.length) {
            this.btnPrevHandler = function (e) {
              e.preventDefault();
              self.prevSlide();
            };
            this.btnPrev.click(this.btnPrevHandler);
          }
          if (this.btnNext.length) {
            this.btnNextHandler = function (e) {
              e.preventDefault();
              self.nextSlide();
            };
            this.btnNext.click(this.btnNextHandler);
          }
          if (this.pagerLinks.length) {
            this.pagerLinksHandler = function (e) {
              e.preventDefault();
              self.numSlide(self.pagerLinks.index(e.currentTarget));
            };
            this.pagerLinks.click(this.pagerLinksHandler);
          }

          // handle autorotation pause on hover
          if (this.options.pauseOnHover) {
            this.hoverHandler = function () {
              clearTimeout(self.timer);
            };
            this.leaveHandler = function () {
              self.autoRotate();
            };
            this.holder.bind({mouseenter: this.hoverHandler, mouseleave: this.leaveHandler});
          }

          // handle holder and slides dimensions
          this.resizeHandler = function () {
            if (!self.animating) {
              if (self.options.stretchSlideToMask) {
                self.resizeSlides();
              }
              self.resizeHolder();
              self.setSlidesPosition(self.currentIndex);
            }
          };
          $(window).bind('load resize orientationchange', this.resizeHandler);
          if (self.options.stretchSlideToMask) {
            self.resizeSlides();
          }

          // handle swipe on mobile devices
          if (this.options.handleTouch && jQuery.fn.hammer && this.slides.length > 1 && isTouchDevice) {
            this.mask.hammer({
              drag_block_horizontal: self.options.vertical ? false : true,
              drag_block_vertical: self.options.vertical ? true : false,
              drag_min_distance: 1
            }).on('touch release ' + (this.options.vertical ? 'swipeup swipedown dragup dragdown' : 'swipeleft swiperight dragleft dragright'), function (ev) {
              switch (ev.type) {
                case (self.options.vertical ? 'dragup' : 'dragright'):
                case (self.options.vertical ? 'dragdown' : 'dragleft'):
                  if (!self.animating) {
                    self.swipeOffset = -self.slideSize + ev.gesture[self.options.vertical ? 'deltaY' : 'deltaX'];
                    self.slider.css(self.animProperty, self.swipeOffset);
                    clearTimeout(self.timer);
                  }
                  ev.gesture.preventDefault();
                  break;
                case (self.options.vertical ? 'swipeup' : 'swipeleft'):
                  if (!self.animating) {
                    self.nextSlide();
                    self.swipeOffset = 0;
                  }
                  ev.gesture.stopDetect();
                  break;
                case (self.options.vertical ? 'swipedown' : 'swiperight'):
                  if (!self.animating) {
                    self.prevSlide();
                    self.swipeOffset = 0;
                  }
                  ev.gesture.stopDetect();
                  break;
                case 'release':
                  if (Math.abs(ev.gesture[self.options.vertical ? 'deltaY' : 'deltaX']) > self.options.swipeThreshold) {
                    if (self.options.vertical) {
                      if (ev.gesture.direction == 'down') {
                        self.prevSlide();
                      } else if (ev.gesture.direction == 'up') {
                        self.nextSlide();
                      }
                    }
                    else {
                      if (ev.gesture.direction == 'right') {
                        self.prevSlide();
                      } else if (ev.gesture.direction == 'left') {
                        self.nextSlide();
                      }
                    }
                  }
                  else {
                    var tmpObj = {};
                    tmpObj[self.animProperty] = -self.slideSize;
                    self.slider.animate(tmpObj, {duration: self.options.animSpeed});
                  }
                  self.swipeOffset = 0;
                  break;
              }
            });
          }

          // start autorotation
          this.autoRotate();
          this.resizeHolder();
          this.setSlidesPosition(this.currentIndex);
        },
        resizeSlides: function () {
          this.slideSize = this.mask[this.options.vertical ? 'height' : 'width']();
          this.slides.css(this.sizeProperty, this.slideSize);
        },
        resizeHolder: function () {
          if (this.options.maskAutoSize) {
            this.mask.css({
              height: this.slides.eq(this.currentIndex).outerHeight(true)
            });
          }
        },
        prevSlide: function () {
          if (!this.animating && this.slides.length > 1) {
            this.direction = -1;
            this.prevIndex = this.currentIndex;
            if (this.currentIndex > 0) this.currentIndex--;
            else this.currentIndex = this.slides.length - 1;
            this.switchSlide();
          }
        },
        nextSlide: function (fromAutoRotation) {
          if (!this.animating && this.slides.length > 1) {
            this.direction = 1;
            this.prevIndex = this.currentIndex;
            if (this.currentIndex < this.slides.length - 1) this.currentIndex++;
            else this.currentIndex = 0;
            this.switchSlide();
          }
        },
        numSlide: function (c) {
          if (!this.animating && this.currentIndex !== c && this.slides.length > 1) {
            this.direction = c > this.currentIndex ? 1 : -1;
            this.prevIndex = this.currentIndex;
            this.currentIndex = c;
            this.switchSlide();
          }
        },
        preparePosition: function () {
          // prepare slides position before animation
          this.setSlidesPosition(this.prevIndex, this.direction < 0 ? this.currentIndex : null, this.direction > 0 ? this.currentIndex : null, this.direction);
        },
        setSlidesPosition: function (index, slideLeft, slideRight, direction) {
          // reposition holder and nearest slides
          if (this.slides.length > 1) {
            var prevIndex = (typeof slideLeft === 'number' ? slideLeft : index > 0 ? index - 1 : this.slides.length - 1);
            var nextIndex = (typeof slideRight === 'number' ? slideRight : index < this.slides.length - 1 ? index + 1 : 0);

            this.slider.css(this.animProperty, this.swipeOffset ? this.swipeOffset : -this.slideSize);
            this.slides.css(this.positionProperty, -9999).eq(index).css(this.positionProperty, this.slideSize);
            if (prevIndex === nextIndex && typeof direction === 'number') {
              var calcOffset = direction > 0 ? this.slideSize * 2 : 0;
              this.slides.eq(nextIndex).css(this.positionProperty, calcOffset);
            } else {
              this.slides.eq(prevIndex).css(this.positionProperty, 0);
              this.slides.eq(nextIndex).css(this.positionProperty, this.slideSize * 2);
            }
          }
        },
        switchSlide: function () {
          // prepare positions and calculate offset
          var self = this;
          var oldSlide = this.slides.eq(this.prevIndex);
          var newSlide = this.slides.eq(this.currentIndex);
          this.animating = true;

          // resize mask to fit slide
          if (this.options.maskAutoSize) {
            this.mask.animate({
              height: newSlide.outerHeight(true)
            }, {
              duration: this.options.animSpeed
            });
          }

          // start animation
          var animProps = {};
          animProps[this.animProperty] = this.direction > 0 ? -this.slideSize * 2 : 0;
          this.preparePosition();
          this.slider.animate(animProps, {
            duration: this.options.animSpeed, complete: function () {
              self.setSlidesPosition(self.currentIndex);

              // start autorotation
              self.animating = false;
              self.autoRotate();

              // onchange callback
              self.makeCallback('onChange', self);
            }
          });

          // refresh classes
          this.refreshState();

          // onchange callback
          this.makeCallback('onBeforeChange', this);
        },
        refreshState: function (initial) {
          // slide change function
          this.slides.removeClass(this.options.activeClass).eq(this.currentIndex).addClass(this.options.activeClass);
          this.pagerLinks.removeClass(this.options.activeClass).eq(this.currentIndex).addClass(this.options.activeClass);

          // display current slide number
          this.currentNumber.html(this.currentIndex + 1);
          this.totalNumber.html(this.slides.length);

          // add class if not enough slides
          this.holder.toggleClass('not-enough-slides', this.slides.length === 1);
        },
        autoRotate: function () {
          var self = this;
          clearTimeout(this.timer);
          if (this.options.autoRotation) {
            this.timer = setTimeout(function () {
              self.nextSlide();
            }, this.options.switchTime);
          }
        },
        makeCallback: function (name) {
          if (typeof this.options[name] === 'function') {
            var args = Array.prototype.slice.call(arguments);
            args.shift();
            this.options[name].apply(this, args);
          }
        },
        destroy: function () {
          // destroy handler
          this.btnPrev.unbind('click', this.btnPrevHandler);
          this.btnNext.unbind('click', this.btnNextHandler);
          this.pagerLinks.unbind('click', this.pagerLinksHandler);
          this.holder.unbind({mouseenter: this.hoverHandler, mouseleave: this.leaveHandler});
          $(window).unbind('load resize orientationchange', this.resizeHandler);
          clearTimeout(this.timer);

          // destroy swipe handler
          if (this.options.handleTouch && $.fn.hammer) {
            this.mask.hammer().off('touch release swipeleft swiperight swipeup swipedown dragup dragdown dragleft dragright');
          }

          // remove inline styles, classes and pagination
          this.holder.removeClass(this.options.galleryReadyClass);
          this.slider.add(this.slides).removeAttr('style');
          if (typeof this.options.generatePagination === 'string') {
            this.pagerHolder.empty();
          }
        }
      };

      // detect device type
      var isTouchDevice = /MSIE 10.*Touch/.test(navigator.userAgent) || ('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch;

      // jquery plugin
      $.fn.scrollAbsoluteGallery = function (opt) {
        return this.each(function () {
          $(this).data('ScrollAbsoluteGallery', new ScrollAbsoluteGallery($.extend(opt, {holder: this})));
        });
      };

      initCycleCarousel();

    }
  };
})(jQuery, Drupal);
