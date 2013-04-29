(function($){
	$.fn.filterXY = function(x, y){
		return this.filter(function(){
			var $this  = $(this),
				offset = $this.offset(),
				width  = $this.width(),
				height = $this.height();

				// console.log(offset.top);

			return offset.left + width >= x
			       && offset.top <= y
			       && offset.top + height >= y;
		});
	}
})(jQuery);

TOCSex = (function() {

	/**
	 *
	 */
	var self = function(toc, principal) {
		this.$toc       = $(toc);
		this.$principal = $(principal);

		var instance   = this;
		var timer      = null;
		var toc_offset = this.$toc.offset().top;

		$(window).resize(function(){
			clearTimeout(timer);

			timer = setTimeout(function() {
				instance.resample();
			}, 100);
		});

		$(window).scroll(function() {
			clearTimeout(timer);

			instance.resample();

			timer = setTimeout(function() {

				var scroll_top = $(window).scrollTop();
				var $current   = instance.current();
				var new_top    = 0;

				if (scroll_top < toc_offset) {
					new_top = 0;
				} else {
					new_top = scroll_top - toc_offset + 10;
				}

				instance.$toc.animate({
					top: new_top + 'px'
				}, 500, 'swing', function() {

					if (!$current.is('h2, h3, h4, h5, h6')) {
						$current = $($current.prevAll('h2, h3, h4, h5, h6')[0]);
					}

					var $anchor = instance.$toc.find('a[href=#' + $current.attr('id') + ']');

					instance.$toc.find('li.active').removeClass('active');

					$anchor.parents('li').addClass('active');
					$anchor.children('li').addClass('active');
				});

				/**

				instance.$toc.fadeOut(100, function() {
					instance.$toc.css({top: new_top + 'px'});

					if (!$current.is('h2, h3, h4, h5, h6')) {
						$current = $($current.prevAll('h2, h3, h4, h5, h6')[0]);
					}

					var $anchor = instance.$toc.find('a[href=#' + $current.attr('id') + ']');

					instance.$toc.find('li.active').removeClass('active');

					$anchor.parents('li').addClass('active');
					$anchor.children('li').addClass('active');

					instance.$toc.fadeIn(300);
				});

				*/


			}, 200);
		});
	};


	/**
	 *
	 */
	self.prototype.current = function() {
		var $current = this.$principal.children().filterXY(this.sampleX, this.sampleY);

		while (!$current.length) {
			this.sampleY += 5;
			$current = this.$principal.children().filterXY(this.sampleX, this.sampleY++);
		}

		return $current;
	}

	/**
	 *
	 */
	self.prototype.resample = function() {
		var $window  = $(window);
		var offset   = this.$principal.offset();
		var width    = this.$principal.outerWidth();

		this.sampleX = Math.floor(offset.left + (width / 2));
		this.sampleY = Math.floor($window.scrollTop() + ($window.height() / 3));
	}


	/**
	 *
	 */
	self.init = function(toc, principal) {
		var tocsex = new self(toc, principal);

		tocsex.resample();
	}

	return self;
})();