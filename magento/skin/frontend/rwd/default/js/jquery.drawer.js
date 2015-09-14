/*!
 * jquery-drawer (https://github.com/alxndrwcz/jquery-drawer)
 * Copyright 2015 Brian Alexandrowicz
 * Licensed under MIT (https://github.com/alxndrwcz/jquery-drawer/blob/master/LICENSE)
 */

(function($) {
	$.fn.drawer = function(options) {
		var settings = $.extend({
            slideSpeed: 500,
            arrowIcon: "&#9660;"
        }, options);
		return this.each(function() {
			$(".drawer-header-icon", this).html(settings.arrowIcon);
			$(".drawer-header", this).click(function(e) {
				e.preventDefault;
				$(this).parent().toggleClass("drawer-item-active")
				$(this).next(".drawer-content").slideToggle( settings.slideSpeed );
				$(".drawer-header-icon", this).toggleClass("drawer-header-icon-active");
			});
		});
	};
}(jQuery));