window.doSubmit = false;

var verifyCallback = function(response) {
    if (response === grecaptcha.getResponse()) {
        window.doSubmit = true;
    }
};

var reCaptchaExpired = function(response) {
   window.doSubmit = false;
};


var onloadCallback = function() {
    var captchaKey = jQuery('.g-recaptcha').attr('data-sitekey');

    grecaptcha.render('g-recaptcha', {
        'sitekey' : captchaKey,
        'callback' : verifyCallback,
        'expired-callback': reCaptchaExpired
    });
};

var onloadCallbackIn = function() {
    window.doSubmit = true;
    var mform = jQuery('#stg-ticket-form');
    mform.submit();

};

(function ($) {
    "use strict";
    }(jQuery));