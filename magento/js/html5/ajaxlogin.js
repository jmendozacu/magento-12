
(function($) {    
    $.fn.youamaAjaxLogin = function(options) {
        var opts = $.extend({}, $.fn.youamaAjaxLogin.defaults, options);
        return start();
        function start() {
            replaceAjaxWindows();            
            removeOriginalJsLocations();            
            openCloseWindowEvents();            
            sendEvents();
        }

        function replaceAjaxWindows() {
            var loginWindow = $('.youama-login-window');
            var registerWindow = $('.youama-register-window');
            var loader = $('.youama-ajaxlogin-loader');
            $('#header-account').html(loginWindow);
            $('#header-account').append(registerWindow);
            $('#header-account').append(loader);
        }

        function removeOriginalJsLocations() {
            /*$('a[href*="customer/account/create"], ' +
                'a[href*="customer/account/login"], ' +
                '.customer-account-login .new-users button')
                .attr('onclick', 'return false;');*/
        }
      
        function openCloseWindowEvents() {
            if (opts.autoShowUp == 'yes'
                && $('.messages').css('display') != 'block') {
                $('.skip-links .skip-account').trigger('click');
                animateShowWindow('login');
            }
            $('#myaccountdj').hover(function() {                
                $('#myaccountdj').removeClass('skip-active');
                animateShowWindow('login');

                return false;
            });



            $('a[href*="customer/account/login"]').click(function() {
                $('.skip-links .skip-account').trigger('click');
            });
            $('.youama-login-window').mouseleave(function() {                
                $('#myaccountdj').removeClass('skip-active');
                $('#header-account').removeClass('skip-active');
                $('.youama-login-window').attr("style","display:none");
            });
            $('.yoauam-switch-window').on('click', function() {
                if ($(this).attr('id') == 'y-to-login') {
                    animateTop();
                    animateCloseWindow('register', false, false);
                    animateShowWindow('login');
                } else {
                    animateTop();
                    animateCloseWindow('login', false, false);
                    animateShowWindow('register');
                }
            });
            $('a[href*="customer/account/create"], .new-users button')
                .on('click', function() {
                //$('.skip-links .skip-account').trigger('click');
                //animateCloseWindow('login', false, false);
                //animateShowWindow('register');
                //return false;
            });
            $('.youama-login-window .close').click(function() {
                animateCloseWindow('login', true, true);
            });
            $('.youama-register-window .close').click(function() {
                animateCloseWindow('register', true, true);
            });
            autoClose();
        }

        function animateTop() {
            $('html,body').animate({scrollTop : 0});
        }

        function sendEvents() {
            $('.youama-register-window button').on('click', function() {
                setDatas('register');
                validateDatas('register');
                if (opts.errors != ''){
                    setError(opts.errors, 'register');
                } else {
                    callAjaxControllerRegistration();
                }
                return false;
            });
            
            $(document).keypress(function(e) {
                if(e.which == 13
                    && $('.youama-login-window').css('display') == 'block') {
                    setDatas('login');
                    validateDatas('login');
                    if (opts.errors != '') {
                        setError(opts.errors, 'login');
                    }
                    else{
                        callAjaxControllerLogin();
                    }
                }
            });

            $('.youama-login-window button').on('click', function() {
                setDatas('login');
                validateDatas('login');
                if (opts.errors != '') {
                    setError(opts.errors, 'login');
                } else {
                    callAjaxControllerLogin();
                }
                return false;
            });
        }
        var timeoutId;

        function animateShowWindow(windowName) {

            var skipContents = $('.skip-content');
            var skipLinks = $('#cart-header');
            $('#cart-header').removeClass('skip-active');
            skipContents.removeClass('skip-active');
            /*$('#myaccountdj').addClass('skip-active');*/
            $('#header-account').addClass('skip-active');
            var test=0;
            if(!test){
                $('#header-cart').hide();
                $('#cart-header').removeClass('skip-active');
                $('.youama-' + windowName + '-window').show();
                
                // $('.youama-' + windowName + '-window' ).slideDown( 500, function() {
                //     // Animation complete.
                // }); 
            }
            test++;
        }

        function animateLoader(windowName, step) {
            // Start
            if (step == 'start') {
                $('.youama-ajaxlogin-loader').fadeIn();
                $('.youama-' + windowName + '-window')
                    .animate({opacity : '0.4'});
            // Stop
            } else {
                $('.youama-ajaxlogin-loader').fadeOut('normal', function() {
                    $('.youama-' + windowName + '-window')
                        .animate({opacity : '1'});
                });
            }
        }

        function animateCloseWindow(windowName, quickly, closeParent) {
            if (opts.stop != true){
                if (quickly == true) {
                    $('.youama-' + windowName + '-window').hide();
                    $('.youama-ajaxlogin-error').hide(function() {
                        if (closeParent) {
                            $('#header-account').removeClass('skip-active');
                        }
                    });
                } else {
                    $('.youama-ajaxlogin-error').fadeOut();
                    $('.youama-' + windowName + '-window').slideUp(function() {
                        if (closeParent) {
                            $('#header-account').removeClass('skip-active');
                        }
                    });
                }
            }
        }

        function validateDatas(windowName) {
            opts.errors = '';

            // Register
            if (windowName == 'register') {
                // There is no last name
                if (opts.lastname.length < 1) {
                    opts.errors = opts.errors + 'nolastname,'
                }

                // There is no first name
                if (opts.firstname.length < 1) {
                    opts.errors = opts.errors + 'nofirstname,'
                }

                // There is no email address
                if (opts.email.length < 1) {
                    opts.errors = opts.errors + 'noemail,'
                // It is not email address
                } else if (validateEmail(opts.email) != true) {
                    opts.errors = opts.errors + 'wrongemail,'
                }

                // There is no password
                if (opts.password.length < 1) {
                    opts.errors = opts.errors + 'nopassword,'
                // Too short password
                } else if (opts.password.length < 6) {
                    opts.errors = opts.errors + 'shortpassword,'
                // Too long password
                } else if (opts.password.length > 16) {
                    opts.errors = opts.errors + 'longpassword,'
                // Passwords doe not match
                } else if (opts.password != opts.passwordsecond) {
                    opts.errors = opts.errors + 'notsamepasswords,'
                }

                // Terms and condition has not been accepted
                if (opts.licence != 'ok') {
                    opts.errors = opts.errors + 'nolicence,'
                }
            // Login
            } else if (windowName == 'login') {
                // There is no email address
                if (opts.email.length < 1) {
                    opts.errors = opts.errors + 'noemail,'
                // It is not email address
                } else if (validateEmail(opts.email) != true) {
                    opts.errors = opts.errors + 'wrongemail,'
                }

                // There is no password
                if (opts.password.length < 1) {
                    opts.errors = opts.errors + 'nopassword,'
                // Too long password
                } else if (opts.password.length > 16) {
                    opts.errors = opts.errors + 'wronglogin,'
                }
            }
        }

        /**
         * Email validator. Retrieve TRUE if it is an email address.
         * @param string emailAddress
         * @returns {boolean}
         */
        function validateEmail(emailAddress) {
            var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;

            if (filter.test(emailAddress)) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Save user input data to property for ajax call.
         * @param string windowName
         */
        function setDatas(windowName) {
            // Register window
            if (windowName == 'register') {
                opts.firstname = $('.youama-' + windowName
                    + '-window #youama-firstname').val();
                opts.lastname = $('.youama-' + windowName
                    + '-window #youama-lastname').val();

                if ($('.youama-' + windowName
                    + '-window input[name="youama-newsletter"]:checked')
                    .length > 0) {
                    opts.newsletter = 'ok';
                } else {
                    opts.newsletter = 'no';
                }

                opts.email = $('.youama-' + windowName
                    + '-window #youama-email').val();
                opts.password = $('.youama-' + windowName
                    + '-window #youama-password').val();
                opts.passwordsecond = $('.youama-' + windowName
                    + '-window #youama-passwordsecond').val();

                if ($('.youama-' + windowName
                    + '-window input[name="youama-licence"]:checked')
                    .length > 0) {
                    opts.licence = 'ok';
                } else {
                    opts.licence = 'no';
                }
            // Login window
            } else if (windowName == 'login') {
                opts.email = $('.youama-' + windowName
                    + '-window #youama-email').val();
                opts.password = $('.youama-' + windowName
                    + '-window #youama-password').val();
            }
        }

        /**
         * Load error messages into windows and show them.
         * @param string errors Comma separated.
         * @param string windowName
         */
        function setError(errors, windowName) {
            $('.youama-' + windowName + '-window .youama-ajaxlogin-error')
                .text('');
            $('.youama-' + windowName + '-window .youama-ajaxlogin-error')
                .hide();

            var errorArr = new Array();
            errorArr = errors.split(',');

            var length = errorArr.length - 1;

            for (var i = 0; i < length; i++) {
                var errorText = $('.ytmpa-' + errorArr[i]).text();

                $('.youama-' + windowName + '-window .err-' + errorArr[i])
                    .text(errorText);
            }

            $('.youama-' + windowName + '-window .youama-ajaxlogin-error')
                .fadeIn();
        }

        /**
         * Ajax call for registration.
         */
        function callAjaxControllerRegistration() {
            // If there is no another ajax calling
            if (opts.stop != true) {

                opts.stop = true;

                // Load the Loader
                animateLoader('register', 'start');

                // Send data
                var ajaxRegistration = jQuery.ajax({
                    url: opts.controllerUrl,
                    type: 'POST',
                    data: {
                    ajax : 'register',
                        firstname : opts.firstname,
                        lastname : opts.lastname,
                        newsletter : opts.newsletter,
                        email : opts.email,
                        password : opts.password,
                        passwordsecond : opts.passwordsecond,
                        licence : opts.licence
                    },
                    dataType: "html"
                });
                // Get data
                ajaxRegistration.done(function(msg) {
                    // If there is error
                    if (msg != 'success') {
                        setError(msg, 'register');
                    // If everything are OK
                    } else {
                        opts.stop = false;
                        animateCloseWindow('register', false, true);
                        // Redirect
                        if (opts.redirection == '1') {
                            window.location = opts.profileUrl;
                        } else {
                            window.location.reload();
                        }
                    }
                    animateLoader('register', 'stop');
                    opts.stop = false;
                });
                // Error on ajax call
                ajaxRegistration.fail(function(jqXHR, textStatus, errorThrown) {
                    opts.stop = false;
                    animateLoader('register', 'stop');
                });
            }
        }

        /**
         * Ajax call for login.
         */
        function callAjaxControllerLogin() {
            // If there is no another ajax calling
            if (opts.stop != true){

                opts.stop = true;

                // Load the Loader
                animateLoader('login', 'start');

                // Send data
                var ajaxRegistration = jQuery.ajax({
                    url: opts.controllerUrl,
                    type: 'POST',
                    data: {
                    ajax : 'login',
                        email : opts.email,
                        password : opts.password
                    },
                    dataType: "html"
                });
                // Get data
                ajaxRegistration.done(function(msg) {
                    // If there is error
                    if (msg != 'success'){
                        setError(msg, 'login');
                    // If everything are OK
                    } else {
                        opts.stop = false;
                        animateCloseWindow('login', false, true);
                        // Redirect
                        if (opts.redirection == '1') {
                            window.location = opts.profileUrl;
                        } else {
                            window.location.reload();
                        }
                    }
                    animateLoader('login', 'stop');
                    opts.stop = false;
                });
                // Error on ajax call
                ajaxRegistration.fail(function(jqXHR, textStatus, errorThrown) {
                    opts.stop = false;
                    animateLoader('login', 'stop');
                });
            }
        }

        /**
         * Close windows if media CSS are changing by resize or menu is closing.
         */
        function autoClose() {
            closeInClose();

            // On resize event
            $(window).resize(function() {
                closeInClose();
            });

            // On click another menu item event
            $('.skip-links a').click(function() {
                closeInClose();
            });
        }

        /**
         * Close windows if menu is not open.
         */
        function closeInClose() {
            if ($('.page-header-container #header-account')
                .hasClass('skip-active') != true) {
                animateCloseWindow('login', true, false);
                animateCloseWindow('register', true, false);
            }
        }
    };

  
    $.fn.youamaAjaxLogin.defaults = {
        redirection : '0',
        windowSize : '',
        stop : false,
        controllerUrl : '',
        profileUrl : '',
        autoShowUp : '',
        errors : '',
        firstname : '',
        lastname : '',
        newsletter : 'no',
        email : '',
        password : '',
        passwordsecond : '',
        licence : 'no'
    };

})(jQuery);