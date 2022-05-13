<?php

namespace Drupal\wmmedia\Plugin\Field\FieldWidget;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraint;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\entity_browser\EntityBrowserInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\MediaInterface;
use Drupal\wmmedia\Plugin\Field\FieldType\MediaFileExtras;
use Drupal\wmmedia\Plugin\Field\FieldType\MediaImageExtras;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @FieldWidget(
 *     id = "wmmedia_media_widget",
 *     label = @Translation("Media Browser with extra fields"),
 *     multiple_values = TRUE,
 *     description = @Translation("A widget containing a media reference with an extra title/description."),
 *     field_types = {
 *         "wmmedia_media_image_extras",
 *         "wmmedia_media_file_extras"
 *     }
 * )
 */
class MediaWidget extends WidgetBase
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;
    /** @var ModuleHandlerInterface */
    protected $moduleHandler;

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->eventDispatcher = $container->get('event_dispatcher');
        $instance->moduleHandler = $container->get('module_handler');

        return $instance;
    }

    public static function defaultSettings()
    {
        return [
            'entity_browser' => '',
            'show_field_label' => true,
            'title_field_enabled' => true,
            'title_field_required' => false,
            'title_field_label' => 'Title',
            'description_field_enabled' => true,
            'description_field_required' => false,
            'description_field_label' => 'Description',
            'field_widget_remove' => true,
            'field_widget_edit' => true,
            'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
            'image_style' => 'medium',
        ] + parent::defaultSettings();
    }

    public function settingsForm(array $form, FormStateInterface $form_state)
    {
        $element = parent::settingsForm($form, $form_state);

        $browsers = [];
        /** @var EntityBrowserInterface $browser */
        foreach ($this->entityTypeManager->getStorage('entity_browser')->loadMultiple() as $browser) {
            $browsers[$browser->id()] = $browser->label();
        }

        $element['entity_browser'] = [
            '#title' => $this->t('Entity browser'),
            '#type' => 'select',
            '#default_value' => $this->getSetting('entity_browser'),
            '#options' => $browsers,
            '#required' => true,
        ];

        $element['field_widget_remove'] = [
            '#title' => $this->t('Display Remove button'),
            '#type' => 'checkbox',
            '#default_value' => $this->getSetting('field_widget_remove'),
        ];

        $element['field_widget_edit'] = [
            '#title' => $this->t('Display Edit button'),
            '#type' => 'checkbox',
            '#default_value' => $this->getSetting('field_widget_edit'),
        ];

        $element['show_field_label'] = [
            '#title' => $this->t('Show field label above the widget'),
            '#type' => 'checkbox',
            '#default_value' => $this->getSetting('show_field_label'),
        ];

        $element['title_field_enabled'] = [
            '#title' => $this->t('Title Enabled'),
            '#type' => 'checkbox',
            '#default_value' => $this->getSetting('title_field_enabled'),
        ];
        $element['title_field_required'] = [
            '#title' => $this->t('Title Required'),
            '#type' => 'checkbox',
            '#default_value' => $this->getSetting('title_field_required'),
        ];
        $element['title_field_label'] = [
            '#title' => $this->t('Title Label'),
            '#type' => 'textfield',
            '#default_value' => $this->getSetting('title_field_label'),
        ];

        $element['description_field_enabled'] = [
            '#title' => $this->t('Description Enabled'),
            '#type' => 'checkbox',
            '#default_value' => $this->getSetting('description_field_enabled'),
        ];
        $element['description_field_required'] = [
            '#title' => $this->t('Description Required'),
            '#type' => 'checkbox',
            '#default_value' => $this->getSetting('description_field_required'),
        ];
        $element['description_field_label'] = [
            '#title' => $this->t('Description Label'),
            '#type' => 'textfield',
            '#default_value' => $this->getSetting('description_field_label'),
        ];

        $element['image_style'] = [
            '#title' => $this->t('Image style'),
            '#type' => 'select',
            '#default_value' => $this->getSetting('image_style'),
            '#options' => array_map(
                static function (ImageStyle $imageStyle) { return $imageStyle->label(); },
                ImageStyle::loadMultiple()
            )
        ];

        return $element;
    }

    public function settingsSummary()
    {
        $summary = [];

        $summary[] = $this->t(
            'Entity browser: @value',
            [
                '@value' => $this->getSetting('entity_browser'),
            ]
        );

        $summary[] = $this->t(
            'Display remove button: @value',
            [
                '@value' => $this->getSetting('field_widget_remove') ? $this->t('Yes') : $this->t('No'),
            ]
        );
        $summary[] = $this->t(
            'Display edit button: @value',
            [
                '@value' => $this->getSetting('field_widget_edit') ? $this->t('Yes') : $this->t('No'),
            ]
        );
        $summary[] = $this->t(
            'Show field label: @value',
            [
                '@value' => $this->getSetting('show_field_label') ? $this->t('Yes') : $this->t('No'),
            ]
        );
        $summary[] = $this->t(
            'Title field: @enabled, @required, Label: @label',
            [
                '@enabled' => $this->getSetting('title_field_enabled') ? $this->t('Yes') : $this->t('No'),
                '@required' => $this->getSetting('title_field_required') ? $this->t('Yes') : $this->t('No'),
                '@label' => $this->getSetting('title_field_label'),
            ]
        );
        $summary[] = $this->t(
            'Description field: @enabled, @required, Label: @label',
            [
                '@enabled' => $this->getSetting('title_field_enabled') ? $this->t('Yes') : $this->t('No'),
                '@required' => $this->getSetting('title_field_required') ? $this->t('Yes') : $this->t('No'),
                '@label' => $this->getSetting('title_field_label'),
            ]
        );

        $summary[] = $this->t(
            'Image style: @style',
            [
                '@style' => $this->getSetting('image_style'),
            ]
        );

        return $summary;
    }

    /**
     * Returns the form for a single field widget, which in our case, is one row of our table.
     *
     * Field widget form elements should be based on the passed-in $element, which
     * contains the base form element properties derived from the field
     * configuration.
     *
     * @param int $delta
     * @return array
     */
    public function formElement(
        FieldItemListInterface $items,
        $delta,
        array $element,
        array &$form,
        FormStateInterface $formState
    ) {
        $fieldName = $this->fieldDefinition->getName();
        $storageKey = self::getStorageKey($element['#field_parents'], $fieldName);

        $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

        $wrapperId = Html::getUniqueId($this->fieldDefinition->getName() . '-container');
        $hiddenTargetId = Html::getUniqueId($this->fieldDefinition->getName() . '-entity-browser-target');
        $buttonBaseId = sha1(implode('-', array_merge($element['#field_parents'], [$fieldName])));

        $ajax = [
            'callback' => [self::class, 'ajaxCallback'],
            'wrapper' => $wrapperId,
        ];

        $element['#prefix'] = '<div id="' . $wrapperId . '" class="form-item">';
        $element['#suffix'] = '</div>';

        /** @var MediaImageExtras[]|MediaFileExtras[] $mediaItems */
        $mediaItems = self::getMediaItems($formState, $storageKey, $items);

        $element['#type'] = 'item';

        if (!$this->getSetting('show_field_label')) {
            $element['#title_display'] = 'invisible';
            $element['#description_display'] = 'invisible';
        }

        $element['container'] = [
            '#type' => 'container',
        ];

        $header = [
            $this->t('Preview'),
            $this->t('Metadata'),
            $this->t('Operations'),
        ];

        $element['container']['table'] = [
            '#type' => 'table',
            '#empty' => $this->t('No media selected'),
            '#header' => $header,
        ];

        if ($cardinality > 1 || $cardinality == -1) {
            $element['container']['table']['#header'][] = $this->t('Order', [], ['context' => 'Sort order']);

            $element['container']['table']['#tabledrag'] = [
                [
                    'action' => 'order',
                    'relationship' => 'sibling',
                    'group' => $this->fieldDefinition->getName() . '-delta-order',
                ],
            ];
        }

        foreach ($mediaItems as $delta => $item) {
            $element['container']['table'][$delta] = [
                '#field_parents' => $form['#parents'],
                '#required' => $delta == 0 && $element['#required'],
                '#delta' => $delta,
                '#weight' => $delta,
                '#attributes' => [
                    'class' => ['draggable'],
                    'data-row-id' => $delta,
                ],
            ];

            $row = &$element['container']['table'][$delta];

            $media = $item->getMedia();
            $file = null;

            if ($media) {
                $sourceField = $media->getSource()->getConfiguration()['source_field'];
                $file = $media->get($sourceField)->entity;
            }

            if (!$media instanceof MediaInterface || !$file instanceof FileInterface) {
                $row['preview'] = [
                    '#plain_text' => 'The referenced media does not exist anymore.',
                ];

                $row['data'] = [
                    '#plain_text' => sprintf('ID: %s', $item->get('entity')->getTargetIdentifier()),
                ];

                $row['operations']['remove'] = [
                    '#access' => (bool) $this->getSetting('field_widget_remove'),
                    '#ajax' => $ajax,
                    '#attributes' => [
                        'data-row-id' => $delta,
                    ],
                    '#depth' => 4,
                    '#limit_validation_errors' => [],
                    '#name' => 'remove_' . $delta . '_' . $buttonBaseId,
                    '#submit' => [[static::class, 'submit']],
                    '#type' => 'submit',
                    '#value' => $this->t('Remove'),
                ];

                continue;
            }

            $row['preview'] = [
                '#markup' => $item->getMedia()->id(),
            ];

            if ($media->bundle() === 'image') {
                $row['preview'] = [
                    '#theme' => 'image_style',
                    '#style_name' => $this->getSetting('image_style'),
                    '#uri' => $file->getFileUri(),
                    '#prefix' => '<a href="' . file_create_url($file->getFileUri()) . '" target="_blank">',
                    '#suffix' => '</a>',
                ];
            }

            if ($media->bundle() === 'file') {
                $row['preview'] = [
                    '#markup' => '<a href="' . file_create_url($file->getFileUri()) . '" target="_blank">' . $file->label() . '</a>',
                ];
            }

            $row['data']['target_id'] = [
                '#value' => $item->getMedia()->id(),
                '#type' => 'hidden',
            ];

            $row['data']['title'] = [
                '#type' => 'textfield',
                '#title' => $this->getSetting('title_field_label'),
                '#default_value' => $item->getTitle(),
                '#size' => 45,
                '#maxlength' => 1024,
                '#access' => (bool) $this->getSetting('title_field_enabled'),
                '#required' => (bool) $this->getSetting('title_field_required'),
            ];

            $row['data']['description'] = [
                '#type' => 'text_format',
                '#title' => $this->getSetting('description_field_label'),
                '#default_value' => $item instanceof MediaImageExtras
                    ? $item->getDescription()
                    : null,
                '#size' => 45,
                '#access' => (bool) $this->getSetting('description_field_enabled'),
                '#required' => (bool) $this->getSetting('description_field_required'),
            ];

            if (
                $this->moduleHandler->moduleExists('allowed_formats')
                && $item->getMedia()->hasField('field_description')
                && ($fieldDefinition = $item->getMedia()->get('field_description')->getFieldDefinition())
                && ($allowedFormats = $fieldDefinition->getThirdPartySettings('allowed_formats'))
            ) {
                $row['data']['description']['#allowed_formats'] = $allowedFormats['allowed_formats'] ?? $allowedFormats;
                $row['data']['description']['#after_build'] = ['_allowed_formats_remove_textarea_help'];
                $row['data']['description']['#allowed_format_hide_settings'] = [
                    'hide_guidelines' => 1,
                    'hide_help' => 1,
                ];
            }

            if ($item->getMedia()->hasField('field_copyright') && $item->getMedia()->get('field_copyright')->value) {
                $row['data']['copyright'] = [
                    '#type' => 'item',
                    '#title' => $this->t('Copyright') . ': ',
                    '#markup' => $item->getMedia()->get('field_copyright')->value,
                ];
            }

            if ($item->getMedia()->hasField('field_alternate') && $item->getMedia()->get('field_alternate')->value) {
                $row['data']['alternate'] = [
                    '#type' => 'item',
                    '#title' => $this->t('Alternate') . ': ',
                    '#markup' => $item->getMedia()->get('field_alternate')->value,
                ];
            }

            $row['operations'] = [
                '#type' => 'actions',
            ];

            if ($this->getSetting('field_widget_edit')) {
                $row['operations']['edit'] = [
                    '#access' => (bool) $this->getSetting('field_widget_edit'),
                    '#markup' => new FormattableMarkup('<a href=":url" target="_blank" class="button">:title</a>', [
                        ':url' => $item->getMedia()->toUrl('edit-form')->toString(),
                        ':title' => $this->t('Edit'),
                    ]),
                ];
            }

            if ($this->getSetting('field_widget_remove')) {
                $row['operations']['remove'] = [
                    '#access' => (bool) $this->getSetting('field_widget_remove'),
                    '#ajax' => $ajax,
                    '#attributes' => [
                        'data-row-id' => $delta,
                        'data-media-id' => $item->getMedia()->id(),
                    ],
                    '#depth' => 4,
                    '#limit_validation_errors' => [],
                    '#name' => 'remove_' . $delta . '_' . $buttonBaseId,
                    '#submit' => [[static::class, 'submit']],
                    '#type' => 'submit',
                    '#value' => $this->t('Remove'),
                ];
            }

            if ($cardinality > 1 || $cardinality == -1) {
                $row['#attributes']['class'][] = 'draggable';

                $row['weight'] = [
                    '#type' => 'weight',
                    '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
                    '#title_display' => 'invisible',
                    '#delta' => $mediaItems->count(),
                    '#default_value' => $delta,
                    '#weight' => 100,
                    '#attributes' => ['class' => [$this->fieldDefinition->getName() . '-delta-order']],
                ];
            }

            $element['container']['table'][$delta] = $row;
        }

        // Add the hidden field where we do housekeeping of incoming entity browser stuff.
        $element['container']['entity_browser_target'] = [
            '#type' => 'hidden',
            '#id' => $hiddenTargetId,
            '#attributes' => ['id' => $hiddenTargetId],
            '#ajax' => $ajax + [
                'event' => 'entity_browser_value_updated',
                'trigger_as' => ['name' => 'add_' . $buttonBaseId],
            ],
        ];

        $element['container']['entity_browser_add'] = [
            '#ajax' => $ajax + [
                    'event' => 'entity_browser_value_updated',
                ],
            '#attributes' => [
                'class' => ['js-hide'],
            ],
            '#depth' => 1,
            '#limit_validation_errors' => [],
            '#name' => 'add_' . $buttonBaseId,
            '#submit' => [[static::class, 'submit']],
            '#type' => 'submit',
        ];

        // Add entity_browser element.
        $element['#selection_mode'] = $selectionMode = $this->getSetting('selection_mode');

        // Enable entity browser if requirements for that are fulfilled.
        if (EntityBrowserElement::isEntityBrowserAvailable($selectionMode, $cardinality, $mediaItems->count())) {
            $persistentData = $this->getPersistentData();

            $element['container']['entity_browser'] = [
                '#type' => 'entity_browser',
                '#entity_browser' => $this->getSetting('entity_browser'),
                '#cardinality' => $cardinality,
                '#selection_mode' => $selectionMode,
                '#entity_browser_validators' => $persistentData['validators'],
                '#widget_context' => $persistentData['widget_context'],
                '#custom_hidden_id' => $hiddenTargetId,
                '#process' => [
                    ['\Drupal\entity_browser\Element\EntityBrowserElement', 'processEntityBrowser'],
                    [static::class, 'processEntityBrowser'],
                ],
            ];
        }

        if ($selectionMode === EntityBrowserElement::SELECTION_MODE_PREPEND) {
            $element['container']['table']['#weight'] = 1;
        }

        $element['container']['#attached']['library'][] = 'entity_browser/common';
        $element['container']['#attached']['library'][] = 'wmmedia/media_widget';
        $element['container']['#attached']['library'][] = 'wmmedia/modal';

        return $element;
    }

    public function massageFormValues(array $values, array $form, FormStateInterface $form_state)
    {
        $return = [];

        // Detect inline entity form used and change the values.
        if (in_array('inline_entity_form', $form['#parents'], true)) {
            $input = $form_state->getUserInput();

            $element = array_merge($form['#parents'], [$this->fieldDefinition->getName()]);
            $newValues = NestedArray::getValue($input, $element);

            if (isset($newValues['container'])) {
                $values = $newValues;
            }
        }

        if (!empty($values['container']['table'])) {
            foreach ($values['container']['table'] as $delta => $value) {
                if (!empty($value['data']['target_id'])) {
                    $return[$delta] = [
                        'target_id' => $value['data']['target_id'],
                    ];
                    if (isset($value['data']['title'])) {
                        $return[$delta]['title'] = $value['data']['title'];
                    }
                    if (isset($value['data']['description']['value'])) {
                        $return[$delta]['description'] = $value['data']['description']['value'];
                    }
                }
            }
        }

        return $return;
    }

    public function flagErrors(
        FieldItemListInterface $items,
        ConstraintViolationListInterface $violations,
        array $form,
        FormStateInterface $form_state
    ) {
        // Taken from Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReferenceBrowserWidget
        /* @var \Symfony\Component\Validator\ConstraintViolation $violation */
        foreach ($violations as $offset => $violation) {
            // The value of the required field is checked through the "not null"
            // constraint, whose message is not very useful. We override it here for
            // better UX.
            if ($violation->getConstraint() instanceof NotNullConstraint) {
                $violations->set($offset, new ConstraintViolation(
                    $this->t('@name field is required.', ['@name' => $items->getFieldDefinition()->getLabel()]),
                    '',
                    [],
                    $violation->getRoot(),
                    $violation->getPropertyPath(),
                    $violation->getInvalidValue(),
                    $violation->getPlural(),
                    $violation->getCode(),
                    $violation->getConstraint(),
                    $violation->getCause()
                ));
            }
        }

        parent::flagErrors($items, $violations, $form, $form_state);
    }

    /**
     * Render API callback: Processes the entity browser element.
     * @param $element
     * @param $complete_form
     * @return array
     */
    public static function processEntityBrowser(&$element, FormStateInterface $form_state, &$complete_form)
    {
        $uuid = key($element['#attached']['drupalSettings']['entity_browser']);

        // This points to the hidden field where Entity Browser should send it's stuff.
        $id = '#' . $element['#custom_hidden_id'];
        $element['#attached']['drupalSettings']['entity_browser'][$uuid]['selector'] = $id;

        return $element;
    }

    public static function getStorageKey(array $fieldParents, string $fieldName): array
    {
        return array_merge($fieldParents, ['#media_items'], [$fieldName, 'items']);
    }

    public static function getMediaItems(
        FormStateInterface $formState,
        array $storageKey,
        ?FieldItemListInterface $items = null
    ): EntityReferenceFieldItemListInterface {
        if (!NestedArray::keyExists($formState->getStorage(), $storageKey)) {
            NestedArray::setValue($formState->getStorage(), $storageKey, $items);
        }

        $mediaItems = NestedArray::getValue($formState->getStorage(), $storageKey);

        return $mediaItems;
    }

    public static function submit(array $form, FormStateInterface $formState): void
    {
        $formState->setRebuild(true);

        $triggering_element = $formState->getTriggeringElement();
        $button = array_pop($triggering_element['#parents']);
        $parents = array_slice($triggering_element['#parents'], 0, -($triggering_element['#depth']));
        $array_parents = array_slice($triggering_element['#array_parents'], 0, -($triggering_element['#depth'] + 1));
        $element = NestedArray::getValue($form, $array_parents);
        $fieldParents = $element['#field_parents'];
        $fieldName = $element['#field_name'];
        $selectionMode = $element['#selection_mode'];
        $storageKey = static::getStorageKey($fieldParents, $fieldName);

        $items = static::getMediaItems($formState, $storageKey);

        switch ($button) {
            case 'entity_browser_add':
                static::addMediaItems($items, $formState, $parents, $selectionMode);
                break;
            case 'remove':
                $index = $triggering_element['#parents'][count($triggering_element['#parents']) - 2];
                $items->removeItem($index);
                // By removing an item in the middle of a list, the deltas of the next items have to change.
                // Because of this, we have to clear existing form input.
                self::clearFormInput($formState);
                break;
        }

        $items->filterEmptyItems();

        NestedArray::setValue($formState->getUserInput(), array_merge($parents, ['container', 'entity_browser_target']), null);
        NestedArray::setValue($formState->getStorage(), static::getStorageKey($fieldParents, $fieldName), $items);
    }

    public static function addMediaItems(
        EntityReferenceFieldItemListInterface $items,
        FormStateInterface $formState,
        array $parents,
        string $selectionMode
    ) {
        // Form state values is empty on a new node form for some reason.
        $media = NestedArray::getValue($formState->getUserInput(), array_merge($parents, ['container', 'entity_browser_target']));
        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

        $entities = array_map(
            function (MediaInterface $media) use ($langcode) {
                return $media->hasTranslation($langcode)
                    ? $media->getTranslation($langcode)
                    : $media;
            },
            EntityBrowserElement::processEntityIds($media)
        );

        $existing = array_map(function ($item) {
            /* @var MediaInterface $item */
            return $item->id();
        }, $items->referencedEntities());

        /** @var MediaInterface $entity */
        foreach ($entities as $entity) {
            if (in_array($entity->id(), $existing, true)) {
                continue;
            }

            $item = [
                'target_id' => $entity->id(),
                'title' => $entity->label(),
            ];

            if ($entity->hasField('field_description')) {
                $item['description'] = $entity->get('field_description')->value;
            }

            if ($selectionMode === EntityBrowserElement::SELECTION_MODE_PREPEND) {
                $value = $items->getValue();
                array_unshift($value, $item);
                $items->setValue($value);
                self::clearFormInput($formState);
            } else {
                $items->appendItem($item);
            }
        }
    }

    public static function ajaxCallback(array &$form, FormStateInterface $formState): array
    {
        $triggering_element = $formState->getTriggeringElement();
        $array_parents = array_slice($triggering_element['#array_parents'], 0, -($triggering_element['#depth'] + 1));

        return NestedArray::getValue($form, $array_parents);
    }

    /**
     * Clears form input.
     *
     * @see https://drupal.stackexchange.com/questions/220185/clear-form-fields-after-ajax-submit
     */
    protected static function clearFormInput(FormStateInterface $form_state): void
    {
        $input = $form_state->getUserInput();
        $clean_keys = $form_state->getCleanValueKeys();
        $clean_keys[] = 'ajax_page_state';

        foreach ($input as $key => $item) {
            if (
                !in_array($key, $clean_keys, true)
                && strpos($key, '_') !== 0
            ) {
                unset($input[$key]);
            }
        }

        $form_state->setUserInput($input);
        $form_state->setRebuild();
    }

    /**
     * Gets data that should persist across Entity Browser renders.
     *
     * @return array
     *   Data that should persist after the Entity Browser is rendered.
     */
    protected function getPersistentData(): array
    {
        return [
            'validators' => [
                'entity_type' => [
                    'type' => $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type'),
                ],
            ],
            'widget_context' => [],
        ];
    }
}
