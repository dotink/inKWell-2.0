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

			var scroll_top = $(window).scrollTop();
			var $current = instance.current();

			if (scroll_top < toc_offset) {
				instance.$toc.css({top: 0 + 'px'});

			} else {
				instance.$toc.css({top: scroll_top - toc_offset + 10 + 'px'});
			}

			instance.resample();

			if (!$current.is('h2, h3, h4, h5, h6')) {
				$current = $($current.prevAll('h2, h3, h4, h5, h6')[0]);
			}

			var $anchor = instance.$toc.find('a[href=#' + $current.attr('id') + ']');

			instance.$toc.find('li.active').removeClass('active');

			$anchor.parents('li').addClass('active');
			$anchor.children('li').addClass('active');

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