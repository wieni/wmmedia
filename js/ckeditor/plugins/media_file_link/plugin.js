(function (jQuery, Drupal, CKEDITOR) {

    "use strict";

    function getSelectedLink(editor)
    {
        const selection = editor.getSelection();
        const selectedElement = selection.getSelectedElement();
        if (selectedElement && selectedElement.is('a')) {
            return selectedElement;
        }

        const range = selection.getRanges(true)[0];

        if (range) {
            range.shrink(CKEDITOR.SHRINK_TEXT);
            return editor.elementPath(range.getCommonAncestor()).contains('a', 1);
        }

        return null;
    }

    CKEDITOR.plugins.add('media_file_link', {
        icons: 'media_file_link',
        init: function(editor) {
            editor.addCommand('open_media_browser', {
                allowedContent: {
                    a: {
                        attributes: {
                            '!href': true,
                            '!data-media-file-link': true,
                        },
                        classes: {}
                    }
                },
                requiredContent: new CKEDITOR.style({
                    element: 'a',
                    attributes: {
                        href: '',
                        'data-media-file-link': '',
                    }
                }),
                modes: {wysiwyg: 1},
                canUndo: true,
                exec: function(editor) {
                    const saveCallback = function (value) {
                        editor.fire('saveSnapshot');

                        let linkElement = getSelectedLink(editor);

                        if (!linkElement && value) {
                            const selection = editor.getSelection();
                            const range = selection.getRanges(1)[0];

                            if (range.collapsed) {
                                const text = new CKEDITOR.dom.text(value, editor.document);
                                range.insertNode(text);
                                range.selectNodeContents(text);
                            }

                            const style = new CKEDITOR.style({
                                element: 'a',
                                attributes: {
                                    'href': 'entity:media/' + value,
                                    'data-media-file-link': value,
                                }
                            });
                            style.type = CKEDITOR.STYLE_INLINE;
                            style.applyToRange(range);
                            range.select();

                            linkElement = getSelectedLink(editor);
                        } else if (linkElement && value) {
                            linkElement.setAttribute('href', 'entity:media/' + value);
                            linkElement.setAttribute('data-media-file-link', value);
                            // In case this was an existing standard link.
                            linkElement.removeAttribute('data-entity-substitution');
                            linkElement.removeAttribute('data-entity-type');
                            linkElement.removeAttribute('data-entity-uuid');
                            linkElement.removeAttribute('data-cke-saved-href');
                        }

                        editor.fire('saveSnapshot');
                    };

                    Drupal.ckeditor.openDialog(
                        editor,
                        editor.config.media_file_link_url,
                        {},
                        saveCallback,
                        editor.config.media_file_link_dialog_options
                    );
                }
            });

            editor.ui.addButton('media_file_link', {
                label: 'Link media file',
                command: 'open_media_browser',
            });
        }
    });

})(jQuery, Drupal, CKEDITOR);
