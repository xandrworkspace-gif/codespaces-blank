(function($){
	var methods = {
		init: function(options){
			var defaults = $.extend({
				classPrefix: 'chStyler',
				css: {
					'position' : 'absolute',
					'left' : '-9999px'
				}
			}, options);

			return this.each(function() {
				if (this.type == 'checkbox' || this.type == 'radio') {
					var $this = $(this),
						$span = $('<span class="' + defaults.classPrefix + '__wrapper" />'),
						$spanCover = $('<span class="' + defaults.classPrefix + '__cover" />');

					$this.css(defaults.css);

					var change = function(e) {
						var $thisSpan = $(this).parent(),
							name = $this[0].name,
							form = $this[0].form;

						if ($this[0].type == 'radio') {
							$('input[name="' + name + '"]', form).parent().removeClass('checked');
							$this.parent().addClass('checked');
						} else {
							if ($this.is(':disabled')) {
								return;
							} else {
								if ($this.is(':checked')) {
									$thisSpan.addClass('checked');
								} else {
									$thisSpan.removeClass('checked');
								}
							}
						}
					};
					$this.on('change', change);

					$this.wrap($span);
					$this.parent().append($spanCover);

					if ($this.is(':checked')) {
						$this.parent().addClass('checked');
					}
					if ($this.is(':disabled')) {
						$this.parent().addClass('disabled');
					}
					if ($this.attr('title')) {
						$this.parent().attr('title', $this.attr('title'));
					}
					if (this.type == 'radio') {
						if ($this.attr('title') != '') {
							$this.parent().attr('title', $this.attr('title'));
						}
						$this.parent().append('<span class="' + defaults.classPrefix + '__color" style="background-color: ' + $this.attr('value') + '" />');
					}


				}
			});
		}
	};

	$.fn.chStyler = function(method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error('Method ' +  method + ' does not exist on jQuery.rSimpleBBEditor');
		}
	};
})(jQuery);