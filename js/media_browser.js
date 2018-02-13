(function ($, Drupal, drupalSettings) {

    'use strict';

    /**
     * Cleanup some stuff
     */
    Drupal.behaviors.wmMediaLibraryMediaBrowserCleaners = {
        attach: function (context) {
            // Get the cardinality
            var cardinality = $('.imgix-browser-container').data('cardinality');
            var multiple = typeof cardinality !== 'undefined' && cardinality !== 1;

            // Hide the submit button and all checkboxes.
            $('.imgix-browser-item input:checkbox').hide();
            if (!multiple) {
                $('.media-image-browser-submit').hide();
                $('.imgix-browser-item .form-type-checkbox').hide();
            }

            $('.imgix-browser-item').click(function() {
                var thisCheck = $(this).find('input:checkbox').first();
                thisCheck.prop("checked", !thisCheck.prop("checked"));
                $(this).toggleClass('active');

                if (!multiple) {
                    $('.media-image-browser-submit').click();
                }
            });

            $('.media-browser-filter-input-search').keypress(function(e) {
                if (e.which === 13) {
                    $('.media-browser-filter-submit').click();
                }
            });
        }
    }
}(jQuery, Drupal, drupalSettings));
