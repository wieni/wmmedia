(function ($) {

    'use strict';

    Drupal.behaviors.wmmedia_image = {
        attach: function (context) {
            if (!Drupal.isBrowserMultiple()) {
                $('.wmmedia__list__select .form-type-checkbox').hide();
            }

            $('.wmmedia__list__select').click(function() {
                const thisCheck = $(this).find('input:checkbox').first();
                thisCheck.prop("checked", !thisCheck.prop("checked"));
                $(this).toggleClass('active');

                if (!Drupal.isBrowserMultiple()) {
                    $('.is-entity-browser-submit').click();
                }
            });
        }
    }

}(jQuery));
