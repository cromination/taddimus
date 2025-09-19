/**
 * Main scripts file for the welcome notice.
 */

/* global hestiaWelcomeNotice */

(function ($) {
    $(document).ready(function () {
        $(document).on('click', '.notice.ti-about-notice .notice-dismiss, .notice.ti-about-notice .ti-return-dashboard span', function () {
            $.ajax({
                async: true,
                type: 'POST',
                data: {
                    action: 'ti_about_dismiss_welcome_notice',
                    nonce: window.hestiaWelcomeNotice.dismissNonce
                },
                url: window.hestiaWelcomeNotice.ajaxurl,
                success: function ( response ) {
                    console.log(response);
                    $(' .ti-about-notice ').fadeOut();
                }
            });
        });
    });
})(jQuery);