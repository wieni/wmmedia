(function ($, Drupal, drupalSettings) {

    'use strict';

    Drupal.behaviors.wmmediaBrowserEditor = {
        attach: function (context, settings) {
            $('.is-editor-entity-browser-submit').hide();
        }
    };

    Drupal.wmmediaBrowserDialog = Drupal.wmmediaBrowserDialog || {
        selectionCompleted: function(event, uuid, entities) {
            $.delay(60000);
            $('.is-editor-entity-browser-submit').click();
        }
    };

}(jQuery, Drupal, drupalSettings));
