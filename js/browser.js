(function ($) {

    'use strict';

    Drupal.behaviors.wmmediaBrowser = {
        attach: function (context) {
            let multipleAttribute = $('.media-browser-container').data('multiple');
            let multiple = typeof multipleAttribute !== 'undefined' && multipleAttribute !== 1;

            if (!multiple) {
                $('.is-entity-browser-submit').hide();
            }

            $('.media-browser-container__list__select input').click(function() {
                if (!multiple) {
                    // In case the modal is called from a field.
                    parent.jQuery(parent.document).find('.entity-browser-modal.ui-dialog .ajax-progress-throbber').css({'z-index': 10003});
                    // In case the modal is called from an editor.
                    parent.jQuery(parent.document).find('.media-file-browser-editor.ui-dialog .ajax-progress-fullscreen').css({'z-index': 502});

                    $('.is-entity-browser-submit').click();
                }
            });

            $('.media-browser-container__filters__name').keypress(function(e) {
                if (e.which === 13) {
                    $('.media-browser-container__filters__submit').click();
                }
            });
        }
    }
}(jQuery));
