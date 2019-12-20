(function ($) {

    'use strict';

    Drupal.behaviors.wmmedia = {
        attach: function (context) {

            if (!Drupal.isBrowserMultiple()) {
                $('.is-entity-browser-submit').hide();
            }

            $('.wmmedia__filters__search').keypress(function(e) {
                if (e.which === 13) {
                    $('.wmmedia__filters__submit').click();
                }
            });
        }
    };

    Drupal.isBrowserMultiple = function() {
        let multipleAttribute = $('.wmmedia').data('multiple');
        return typeof multipleAttribute !== 'undefined' && multipleAttribute !== 1;
    }

}(jQuery));
