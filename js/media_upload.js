(function ($, Drupal) {
    'use strict';

    Drupal.behaviors.wmMediaLibraryMediaUpload = {
        attach: function () {
            var $form = $('#entity-browser-images-form');
            var $button = $form.find('.is-entity-browser-submit');

            $form.once('submitFix').each(function () {
                $form.on('submit', function (e) {
                    e.stopPropagation();

                    if (!$(document.activeElement).is($button)) {
                        // Make the button the triggering element by focusing & trigger click, all in new thread, because 'return false'
                        setTimeout(function () {
                            $button.focus().click();
                        }, 0);

                        return false;
                    }
                });
            });
        }
    }
}(jQuery, Drupal));
