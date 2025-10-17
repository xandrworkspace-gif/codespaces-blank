(function($, w, d) {
	var methods = {
		init: function(options) {
			var defaults = $.extend(true, {
				skin: 'default'
			}, options);

			return this.each(function() {
				var $$ = $(this),
					self = this,

					fn = arguments.callee,

					prefix = defaults.skin + '-select',

					el = {},
					options = [],

					keycode = {
						'tab': 9,
						'enter': 13,
						'esc': 27,
						'space': 32,
						'left': 37,
						'up': 38,
						'right': 39,
						'down': 40
					},

					disabled = false,
					add_class = '';

				if (this.attributes.disabled !== undefined) {
					disabled = true;
					add_class += ' disabled';
				}

				if (typeof defaults.skin !== 'string') {
					$.error('>>> [jQuery.rSelect] "skin" option must be a string');
					return;
				}

				el.wrap = $$.wrap('<span class="select ' + prefix + add_class + '" tabindex="' + self.tabIndex + '" />').parent();
				el.wrap_inner = $$.wrap('<span class="wrap_inner" />').parent();

				el.button = $('<span class="button"><span class="button_inner" /></span>');
				el.value = $('<span class="value" />');

				el.dropdown = $('<span class="dropdown" />');
				el.dropdown_top = $('<span class="dropdown_top"><span /></span>');
				el.dropdown_list = $('<span class="dropdown_list"><span class="dropdown_list_inner" /></span>');
				el.dropdown_bottom = $('<span class="dropdown_bottom"><span /></span>');

				el.wrap
					.append(el.button)
					.append(el.value)
					.append(el.dropdown);

				el.dropdown
					.append(el.dropdown_top)
					.append(el.dropdown_list)
					.append(el.dropdown_bottom);

				// fill dropdown_list with options
				$$.find('option').each(function() {
					var opt = {},
						dropdown_list = el.dropdown_list.find('span.dropdown_list_inner');

					opt.index = this.index;
					opt.value = this.value;
					opt.text = this.text;

					opt.elem = $('<span class="option" />').appendTo(dropdown_list);

					if (opt.index === self.selectedIndex) {
						opt.elem.addClass('option_selected');
					}

					opt.elem
						.html(opt.text)
						.on({
							mouseenter: function() {
								opt.elem.addClass('option_hover');
							},
							mouseleave: function() {
								opt.elem.removeClass('option_hover');
							},
							click: function(e) {
								e.stopPropagation();

								$.each(options, function(k, v) {
									v.elem.removeClass('option_selected');
								});

								opt.elem.addClass('option_selected');

								self.selectedIndex = opt.index;
								el.value.html(opt.text);

								el.wrap.removeClass(prefix + '_opened');
								el.wrap.removeClass(prefix + '_focus');

								if (typeof(phone_country_code_change) === 'function') {
									phone_country_code_change();
								}

								if (typeof(popup_select_change) === 'function') {
									popup_select_change(opt.value);
								}

							}
						})

					if(this.dataset.color) {
						$('<span class="profile-message-color-list" style="background-color: '+this.dataset.color+'" />').prependTo(opt.elem);
					}
					if(this.dataset.picture) {
						$('<span class="profile-message-image-list" style="background: url(\''+this.dataset.picture+'\') '+this.dataset.picwidth+' '+this.dataset.picheight+'" />').prependTo(opt.elem);
					}
					if(this.value) {
						opt.elem.attr('value', this.value);
					}

					options.push(opt);
				});
				el.value.css('width', el.wrap.innerWidth() - parseInt(el.value.css('left')) - parseInt(el.value.css('right')));
				el.value.html(self.options[self.selectedIndex].text);

				// store data
				$$.data({
					'tabIndex': self.tabIndex
				});

				self.tabIndex = -1;

				if (disabled) return;
				// events
				$$.on({
					update: function() {
						this.tabIndex = $$.data('tabIndex');

						$$.insertBefore(el.wrap);
						el.wrap.remove();

						$$.rSelect(defaults);
					}
				});

				$(d).on({
					click: function() {
						el.wrap.removeClass(prefix + '_opened');
					}
				});

				el.wrap
					.on({
						mouseenter: function() {
							el.wrap.addClass(prefix + '_hover');
						},
						mouseleave: function() {
							el.wrap.removeClass(prefix + '_hover');
						},
						mousedown: function() {
							el.wrap.addClass(prefix + '_pressed');
						},
						mouseup: function() {
							el.wrap.removeClass(prefix + '_pressed');
						},
						click: function(e) {
							e.stopPropagation();
							el.wrap.toggleClass(prefix + '_opened');
						},
						dblclick: function(e) {
							e.preventDefault();
						},
						selectstart: function(e) {
							e.preventDefault();
						},
						focus: function() {
							el.wrap.addClass(prefix + '_focus');
						},
						blur: function() {
							el.wrap.removeClass(prefix + '_focus');
							el.wrap.removeClass(prefix + '_opened');
						},
						keyup: function(e) {
							e.preventDefault();
							e.stopPropagation();

							switch (e.keyCode) {
								case keycode.enter:
									el.wrap.toggleClass(prefix + '_opened');
									break;

								case keycode.esc:
									el.wrap.removeClass(prefix + '_opened');
									break;

								case keycode.space:
									el.wrap.addClass(prefix + '_opened');
									break;

								case keycode.left:
								case keycode.up:
									if (self.selectedIndex > 0) {
										self.selectedIndex--;
									}

									$.each(options, function(k, v) {
										v.elem.removeClass('option_selected');
									});

									options[self.selectedIndex].elem.addClass('option_selected');

									el.value.html(options[self.selectedIndex].text);
									break;
								case keycode.right:
								case keycode.down:
									if (self.selectedIndex < self.options.length - 1) {
										self.selectedIndex++;
									}

									$.each(options, function(k, v) {
										v.elem.removeClass('option_selected');
									});

									options[self.selectedIndex].elem.addClass('option_selected');

									el.value.html(options[self.selectedIndex].text);
									break;
							}
						}
					});
			});
		}
	};

	$.fn.rSelect = function(method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error('>>> [jQuery.rSelect] method ' +  method + ' does not exist');
		} 
	};
})(jQuery, window, document);