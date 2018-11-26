<?php

namespace Drupal\wmmedia\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraint;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\entity_browser\FieldWidgetDisplayManager;
use Drupal\media\MediaInterface;
use Drupal\wmmedia\Event\MediaWidgetRenderEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * @FieldWidget(
 *   id = "wmmedia_media_widget",
 *   label = @Translation("Media Browser with extra fields"),
 *   multiple_values = TRUE,
 *   description = @Translation("A widget containing a media reference with an extra title/description."),
 *   field_types = {
 *     "wmmedia_media_image_extras",
 *     "wmmedia_media_file_extras"
 *   }
 * )
 */
class MediaWidget extends WidgetBase implements ContainerFactoryPluginInterface
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;

    /** @var FieldWidgetDisplayManager */
    protected $fieldDisplayManager;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * Constructs widget plugin.
     *
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
     *   The definition of the field to which the widget is associated.
     * @param array $settings
     *   The widget settings.
     * @param array $third_party_settings
     *   Any third party settings.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   Entity type manager service.
     * @param FieldWidgetDisplayManager $fieldDisplayManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        $plugin_id,
        $plugin_definition,
        FieldDefinitionInterface $field_definition,
        array $settings,
        array $third_party_settings,
        EntityTypeManagerInterface $entity_type_manager,
        FieldWidgetDisplayManager $fieldDisplayManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
        $this->entityTypeManager = $entity_type_manager;
        $this->fieldDisplayManager = $fieldDisplayManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $plugin_id,
            $plugin_definition,
            $configuration['field_definition'],
            $configuration['settings'],
            $configuration['third_party_settings'],
            $container->get('entity_type.manager'),
            $container->get('plugin.manager.entity_browser.field_widget_display'),
            $container->get('event_dispatcher')
        );
    }

    /**
     * {@inheritdoc}
     */
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
            'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
        ] + parent::defaultSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state)
    {
        $element = parent::settingsForm($form, $form_state);

        $browsers = [];
        /** @var \Drupal\entity_browser\EntityBrowserInterface $browser */
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

        return $element;
    }

    /**
     * {@inheritdoc}
     */
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
                '@value' => $this->getSetting('field_widget_remove') ? $this->t('Yes'): $this->t('No'),
            ]
        );
        $summary[] = $this->t(
            'Show field label: @value',
            [
                '@value' => $this->getSetting('show_field_label') ? $this->t('Yes'): $this->t('No'),
            ]
        );
        $summary[] = $this->t(
            'Title field: @enabled, @required, Label: @label',
            [
                '@enabled' => $this->getSetting('title_field_enabled') ? $this->t('Yes'): $this->t('No'),
                '@required' => $this->getSetting('title_field_required') ? $this->t('Yes'): $this->t('No'),
                '@label' => $this->getSetting('title_field_label'),
            ]
        );
        $summary[] = $this->t(
            'Description field: @enabled, @required, Label: @label',
            [
                '@enabled' => $this->getSetting('title_field_enabled') ? $this->t('Yes'): $this->t('No'),
                '@required' => $this->getSetting('title_field_required') ? $this->t('Yes'): $this->t('No'),
                '@label' => $this->getSetting('title_field_label'),
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
     * @param FieldItemListInterface $items
     * @param int $delta
     * @param array $element
     * @param array $form
     * @param FormStateInterface $formState
     * @return array
     */
    public function formElement(
        FieldItemListInterface $items,
        $delta,
        array $element,
        array &$form,
        FormStateInterface $formState
    ) {
        // Settings.
        $fieldName = $this->fieldDefinition->getName();
        $storageKey = $this->getStorageKey($element['#field_parents'], $fieldName);

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

        // Get the items we need.
        $mediaItems = $this->getMediaItems($formState, $storageKey, $items);

        // Build the form.
        if ($this->getSetting('show_field_label')) {
            $classes = ['label'];
            if ($element['#required']) {
                $classes[] = 'form-required';
                $classes[] = 'required';
            }

            $element['title'] = [
                '#attributes' => ['class' => $classes],
                '#tag' => 'h4',
                '#type' => 'html_tag',
                '#value' => $element['#title'],
            ];

            if (isset($element['#description'])) {
                $element['description'] = [
                    '#attributes' => ['class' => ['description'], 'style' => 'margin-top: -10px'],
                    '#tag' => 'div',
                    '#type' => 'html_tag',
                    '#value' => $element['#description'],
                    '#weight' => 1,
                ];
            }
        }

        // Create our container.
        $element['container'] = [
            '#type' => 'container',
        ];

        // Construct the table.
        $header = [
            $this->t('Preview'),
            $this->t('Metadata'),
        ];
        if ($this->getSetting('field_widget_remove')) {
            $header[] = $this->t('Delete');
        }

        $element['container']['table'] = [
            '#type' => 'table',
            '#empty' => $this->t('No media selected'),
            '#header' => $header
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

            if (!$item->getMedia()) {
                continue;
            }

            $row = [
                '#field_parents' => $form['#parents'],
                '#required' => $delta == 0 && $element['#required'],
                '#delta' => $delta,
                '#weight' => $delta,
                '#attributes' => [
                    'class' => ['draggable'],
                    'data-row-id' => $delta,
                ],
            ];

            /* @var MediaWidgetRenderEvent $event */
            $event = $this->eventDispatcher->dispatch(
                MediaWidgetRenderEvent::NAME,
                new MediaWidgetRenderEvent($item->getMedia()->id())
            );

            $row['preview'] = [
                '#markup' => $item->getMedia()->id(),
            ];

            if ($event->getPreview()) {
                $row['preview'] = $event->getPreview();
            }

            $row['data']['target_id'] = [
                '#value' => $item->getMedia()->id(),
                '#type' => 'hidden',
            ];

            // Title.
            $title = null;
            if (method_exists($item, 'getTitle') && !empty($item->getTitle())) {
                $title = $item->getTitle();
            }

            $row['data']['title'] = [
                '#type' => 'textfield',
                '#title' => $this->getSetting('title_field_label'),
                '#default_value' => $title,
                '#size' => 45,
                '#maxlength' => 1024,
                '#access' => (bool) $this->getSetting('title_field_enabled'),
                '#required' => (bool) $this->getSetting('title_field_required'),
            ];

            // Description.
            $description = null;
            if (method_exists($item, 'getDescription') && !empty($item->getDescription())) {
                $description = $item->getDescription();
            }

            $row['data']['description'] = [
                '#type' => 'text_format',
                '#title' => $this->getSetting('description_field_label'),
                '#default_value' => $description,
                '#size' => 45,
                '#access' => (bool) $this->getSetting('description_field_enabled'),
                '#required' => (bool) $this->getSetting('description_field_required'),
                '#allowed_format_hide_settings' => [
                    'hide_guidelines' => 1,
                    'hide_help' => 1,
                ],
                '#allowed_formats' => ['plain_text'],
                '#after_build' => ['_allowed_formats_remove_textarea_help'],
            ];

            // Copyright.
            if ($item->getMedia()->hasField('field_copyright') && $item->getMedia()->get('field_copyright')->value) {
                $row['data']['copyright'] = [
                    '#type' => 'item',
                    '#title' => $this->t('Copyright') . ': ',
                    '#markup' => $item->getMedia()->get('field_copyright')->value,
                ];
            }

            // Alternate.
            if ($item->getMedia()->hasField('field_alternate') && $item->getMedia()->get('field_alternate')->value) {
                $row['data']['alternate'] = [
                    '#type' => 'item',
                    '#title' => $this->t('Alternate') . ': ',
                    '#markup' => $item->getMedia()->get('field_alternate')->value,
                ];
            }

            if ($this->getSetting('field_widget_remove')) {
                $row['remove'] = [
                    '#access' => (bool) $this->getSetting('field_widget_remove'),
                    '#ajax' => $ajax,
                    '#attributes' => [
                        'data-row-id' => $delta,
                        'data-media-id' => $item->getMedia()->id(),
                    ],
                    '#depth' => 3,
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
        $selectionMode = $this->getSetting('selection_mode');

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

        $element['container']['#attached']['library'][] = 'entity_browser/common';
        $element['container']['#attached']['library'][] = 'wmmedia/media.dialog';

        return $element;
    }

    /**
     * Massages the form values into the format expected for field values.
     *
     * @param array $values
     *   The submitted form values produced by the widget.
     *   - If the widget does not manage multiple values itself, the array holds
     *     the values generated by the multiple copies of the $element generated
     *     by the formElement() method, keyed by delta.
     *   - If the widget manages multiple values, the array holds the values
     *     of the form element generated by the formElement() method.
     * @param array $form
     *   The form structure where field elements are attached to. This might be a
     *   full form structure, or a sub-element oWf a larger form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The form state.
     *
     * @return array
     *   An array of field values, keyed by delta.
     */
    public function massageFormValues(array $values, array $form, FormStateInterface $form_state)
    {
        $return = [];

        // Detect inline entity form used and change the values.
        if (in_array('inline_entity_form', $form['#parents'])) {
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
                        'target_id' => $value['data']['target_id']
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

    /**
     * @param FieldItemListInterface $items
     * @param ConstraintViolationListInterface $violations
     * @param array $form
     * @param FormStateInterface $form_state
     */
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
     * @param FormStateInterface $form_state
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

    /**
     * @param array $fieldParents
     * @param string $fieldName
     * @return array
     */
    public static function getStorageKey(array $fieldParents, $fieldName)
    {
        return array_merge($fieldParents, ['#media_items'], [$fieldName, 'items']);
    }

    /**
     * @param \Drupal\Core\Form\FormStateInterface $formState
     * @param array $storageKey
     * @param \Drupal\Core\Field\FieldItemListInterface $items
     * @return \Drupal\Core\Field\EntityReferenceFieldItemListInterface
     */
    public static function getMediaItems(
        FormStateInterface $formState,
        $storageKey,
        FieldItemListInterface $items = null
    ) {
        if (!NestedArray::keyExists($formState->getStorage(), $storageKey)) {
            NestedArray::setValue($formState->getStorage(), $storageKey, $items);
        }

        $mediaItems = NestedArray::getValue($formState->getStorage(), $storageKey);
        return $mediaItems;
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $formState
     */
    public static function submit(array $form, FormStateInterface $formState)
    {
        $formState->setRebuild(true);

        $triggering_element = $formState->getTriggeringElement();
        $button = array_pop($triggering_element['#parents']);
        $parents = array_slice($triggering_element['#parents'], 0, -($triggering_element['#depth']));
        $array_parents = array_slice($triggering_element['#array_parents'], 0, -($triggering_element['#depth'] + 1));
        $element = NestedArray::getValue($form, $array_parents);
        $fieldParents = $element['#field_parents'];
        $fieldName = $element['#field_name'];
        $storageKey = static::getStorageKey($fieldParents, $fieldName);

        $items = static::getMediaItems($formState, $storageKey);

        switch ($button) {
            case 'entity_browser_add':
                static::addMediaItems($items, $formState, $parents);
                break;
            case 'remove':
                $index = array_pop($triggering_element['#parents']);
                $items->removeItem($index);
                break;
        }

        $items->filterEmptyItems();

        NestedArray::setValue($formState->getUserInput(), array_merge($parents, ['container', 'entity_browser_target']), null);
        NestedArray::setValue($formState->getStorage(), static::getStorageKey($fieldParents, $fieldName), $items);
    }

    /**
     * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
     * @param \Drupal\Core\Form\FormStateInterface $formState
     * @param array $parents
     */
    public static function addMediaItems(
        EntityReferenceFieldItemListInterface $items,
        FormStateInterface $formState,
        array $parents
    ) {
        // Form state values is empty on a new node form for some reason.
        $media = NestedArray::getValue($formState->getUserInput(), array_merge($parents, ['container', 'entity_browser_target']));
        $entities = array_map(
            function (MediaInterface $media) {
                return $media->hasTranslation('nl')
                    ? $media->getTranslation('nl')
                    : $media;
            },
            EntityBrowserElement::processEntityIds($media)
        );

        $existing = array_map(function($item) {
            /* @var \Drupal\media\Entity\Media $item */
            return $item->id();
        }, $items->referencedEntities());

        /** @var MediaInterface $entity */
        foreach ($entities as $entity) {
            if (in_array($entity->id(), $existing)) {
                continue;
            }

            $item = [
                'target_id' => $entity->id(),
                'title' => $entity->label()
            ];

            if ($entity->hasField('field_description')) {
                $item['description'] = $entity->get('field_description')->value;
            }

            $items->appendItem($item);
        }
    }

    /**
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $formState
     * @return array
     */
    public static function ajaxCallback(array &$form, FormStateInterface $formState)
    {
        $triggering_element = $formState->getTriggeringElement();
        $array_parents = array_slice($triggering_element['#array_parents'], 0, -($triggering_element['#depth'] + 1));
        return NestedArray::getValue($form, $array_parents);
    }

    /**
     * Gets data that should persist across Entity Browser renders.
     *
     * @return array
     *   Data that should persist after the Entity Browser is rendered.
     */
    protected function getPersistentData()
    {
        return [
            'validators' => [
                'entity_type' => [
                    'type' => $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type')
                ],
            ],
            'widget_context' => [],
        ];
    }
}
