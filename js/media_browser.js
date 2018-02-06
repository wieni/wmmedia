(function ($, Drupal, drupalSettings) {

    'use strict';

    /**
     * Cleanup some stuff
     */
    Drupal.behaviors.wmMediaLibraryMediaBrowserCleaners = {
        attach: function (context) {
            // Hide the submit button and all checkboxes.
            $('.imgix-browser-item input:checkbox').hide();

            $('.imgix-browser-item').click(function() {
                var thisCheck = $(this).find('input:checkbox').first();
                thisCheck.prop("checked", !thisCheck.prop("checked"));
                $(this).toggleClass('active');
            });

            $('.media-browser-filter-input-search').keypress(function(e) {
                if (e.which === 13) {
                    $('.media-browser-filter-submit').click();
                }
            });
        }
    }
}(jQuery, Drupal, drupalSettings));
