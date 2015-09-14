
/**
 * Copyright [2014] [Dexxtz]
 *
 * @package   Dexxtz_Scrolltop
 * @author    Dexxtz
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

function scrollTop(distance, speed) {
	jQuery(window).scroll(function () {
		if (jQuery(this).scrollTop() > distance) {
			jQuery('.dextz-scrolltop').fadeIn();
		} else {
			jQuery('.dextz-scrolltop').fadeOut();
		}
	});
	jQuery('.dextz-scrolltop').click(function() {
		jQuery('body,html').animate({scrollTop:0},speed);
	});
}