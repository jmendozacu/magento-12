jQuery(document).ready(function() {
	
	jQuery("#AgepopupLink").fancybox({		
		'closeBtn'   : false,
		
		helpers     : {
			overlay : {
			    speedIn  : 0,
			    speedOut : 300,
			    opacity  : 0.5,
			    css      : {
			        cursor : 'default'
			    },
			    closeClick: false
			}
		},
		keys  : {
			close  : null // escape key
		}

	});

	if(readCookie('over18Popup') !=1 ) {
		jQuery("#AgepopupLink").trigger('click');
	}
});

/*console.log(readCookie('over18Popup'));
*/

jQuery(document).ready(function(){  
    jQuery('.plusheh').on('click', function(event){
        event.stopPropagation();
        jQuery.fancybox.close();
        createCookie('over18Popup','1',3);	
    });
});  

function closeOverAgePopup()
{
	
	jQuery.fancybox.close();
	jQuery('#AgepopupLink').hide();
	//createCookie('over18Popup','1',3);	
}

function createCookie(name, value, days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    } else var expires = "";
    document.cookie = escape(name) + "=" + escape(value) + expires + "; path=/";
}

//createCookie('onetimepopup','1',1);

function readCookie(name) {
    var nameEQ = escape(name) + "=";
    //alert(nameEQ);
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return unescape(c.substring(nameEQ.length, c.length));
    }
    return null;
}


function eraseCookie(name) {
    createCookie(name, "", -1);
}

function showproductdetail(id){
	//alert(id);
	//jQuery('div.demo-show:eq(0) > div').hide();  	
	jQuery('#productid'+id).slideToggle('fast');
	
}


function showshipproductdetailacc(id){
	jQuery('#shippingproductid'+id).slideToggle('fast');
}

function showstoreproductdetailacc(id){
	jQuery('#storeproductid'+id).slideToggle('fast');
}

function showchatanexpert(id){
	jQuery('#chatproductid'+id).slideToggle('fast');	
}

if(jQuery('.addlinksnew')){
	jQuery('#myaccountdj').hover(function() {
		
		var skipContents = jQuery('.skip-content');
		var skipLinks = jQuery('#cart-header');

		jQuery('#cart-header').removeClass('skip-active');
		skipContents.removeClass('skip-active');

		jQuery('#myaccountdj').addClass('skip-active');
		jQuery('#header-account').addClass('skip-active');
		jQuery('#header-account').show();
	});
}
