(function ($) {

    'use strict';

    Drupal.behaviors.wmmediaBrowser = {
        attach: function (context) {
            $('.wmmedia__list__select input').click(function() {
                if (!Drupal.isBrowserMultiple()) {
                    // In case the modal is called from a field.
                    parent.jQuery(parent.document).find('.entity-browser-modal.ui-dialog .ajax-progress-throbber').css({'z-index': 10003});
                    // In case the modal is called from an editor.
                    parent.jQuery(parent.document).find('.media-file-browser-editor.ui-dialog .ajax-progress-fullscreen').css({'z-index': 502});

                    $('.is-entity-browser-submit').click();
                }
            });
        }
    };
}(jQuery));
