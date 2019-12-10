<?php

namespace Drupal\wmmedia\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReferenceBrowserWidget;
use Drupal\wmmedia\Service\RenderFileTrait;

/**
 * @FieldWidget(
 *     id = "media_file",
 *     label = @Translation("Media file"),
 *     description = @Translation("Uses entity browser to select files."),
 *     multiple_values = TRUE,
 *     field_types = {
 *         "entity_reference"
 *     }
 * )
 */
class MediaFile extends EntityReferenceBrowserWidget
{

    use RenderFileTrait;

    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $formState): array
    {
        $entities = $this->formElementEntities($items, $element, $formState);

        // Get correct ordered list of entity IDs.
        $ids = array_map(
            static function (EntityInterface $entity) {
                return $entity->id();
            },
            $entities
        );

        // We store current entity IDs as we might need them in future requests. If
        // some other part of the form triggers an AJAX request with
        // #limit_validation_errors we won't have access to the value of the
        // target_id element and won't be able to build the form as a result of
        // that. This will cause missing submit (Remove, Edit, ...) elements, which
        // might result in unpredictable results.
        $formState->set(['entity_browser_widget', $this->getFormStateKey($items)], $ids);

        $hiddenId = Html::getUniqueId('edit-' . $this->fieldDefinition->getName() . '-target-id');
        $detailsId = Html::getUniqueId('edit-' . $this->fieldDefinition->getName());

        $element += [
            '#id' => $detailsId,
            '#type' => 'container',
            '#required' => $this->fieldDefinition->isRequired(),
            // We are not using Entity browser's hidden element since we maintain
            // selected entities in it during entire process.
            'label' => [
                '#required' => $this->fieldDefinition->isRequired(),
                '#title' => $element['#title'],
                '#type' => 'label',
            ],
            'target_id' => [
                '#type' => 'hidden',
                '#id' => $hiddenId,
                // We need to repeat ID here as it is otherwise skipped when rendering.
                '#attributes' => ['id' => $hiddenId],
                '#default_value' => implode(' ', array_map(
                    static function (EntityInterface $item) {
                        return $item->getEntityTypeId() . ':' . $item->id();
                    },
                    $entities
                )),
                // #ajax is officially not supported for hidden elements but if we
                // specify event manually it works.
                '#ajax' => [
                    'callback' => [get_class($this), 'updateWidgetCallback'],
                    'wrapper' => $detailsId,
                    'event' => 'entity_browser_value_updated',
                ],
            ],
        ];

        // Get configuration required to check entity browser availability.
        $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
        $selectionMode = $this->getSetting('selection_mode');

        // Enable entity browser if requirements for that are fulfilled.
        if (EntityBrowserElement::isEntityBrowserAvailable($selectionMode, $cardinality, count($ids))) {
            $persistentData = $this->getPersistentData();

            $element['entity_browser'] = [
                '#type' => 'entity_browser',
                '#entity_browser' => $this->getSetting('entity_browser'),
                '#cardinality' => $cardinality,
                '#selection_mode' => $selectionMode,
                '#default_value' => $entities,
                '#entity_browser_validators' => $persistentData['validators'],
                '#widget_context' => $persistentData['widget_context'],
                '#custom_hidden_id' => $hiddenId,
                '#process' => [
                    [EntityBrowserElement::class, 'processEntityBrowser'],
                    [static::class, 'processEntityBrowser'],
                ],
            ];
        }

        $field_parents = $element['#field_parents'];

        $element['current'] = $this->displayCurrentSelection($detailsId, $field_parents, $entities);

        $element['#attached']['library'][] = 'entity_browser/entity_reference';
        $element['#attached']['library'][] = 'wmmedia/media_file_widget';

        return $element;
    }

    /**
     * @inheritDoc
     */
    protected function displayCurrentSelection($detailsId, array $fieldParents, array $entities): array
    {
        $targetEntityType = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');

        $fieldWidgetDisplay = $this->fieldDisplayManager->createInstance(
            $this->getSetting('field_widget_display'),
            $this->getSetting('field_widget_display_settings') + ['entity_type' => $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type')]
        );

        $classes = [
            'entities-list',
            'entities-list-file',
            Html::cleanCssIdentifier("entity-type--$targetEntityType"),
        ];
        if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() !== 1) {
            $classes[] = 'sortable';
        }

        // The "Replace" button will only be shown if this setting is enabled in the
        // widget, and there is only one entity in the current selection.
        $replaceButtonAccess = $this->getSetting('field_widget_replace') && (count($entities) === 1);

        return [
            '#theme_wrappers' => ['container'],
            '#attributes' => ['class' => $classes],
            'items' => array_map(
                function (ContentEntityInterface $entity, $rowId) use ($fieldWidgetDisplay, $detailsId, $fieldParents, $replaceButtonAccess) {
                    /* @var \Drupal\media\Entity\Media $entity */
                    $editButtonAccess = $this->getSetting('field_widget_edit') && $entity->access('update', $this->currentUser);
                    $editButtonAccess &= $this->moduleHandler->moduleExists('file_entity');

                    /* @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $list */
                    $list = $entity->get('field_media_file');
                    $file = $list->referencedEntities()[0] ?? null;

                    $namePattern = $this->fieldDefinition->getName() . '_%s_' . $entity->id() . '_' . $rowId . '_' . md5(json_encode($fieldParents, JSON_THROW_ON_ERROR));

                    return [
                        '#theme_wrappers' => ['container'],
                        '#attributes' => [
                            'class' => ['item-container', Html::getClass($fieldWidgetDisplay->getPluginId())],
                            'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
                            'data-row-id' => $rowId,
                        ],
                        'display' => [
                            '#type' => 'container',
                            'file' => $this->renderFile($file, $entity->label()),
                        ],
                        'remove_button' => [
                            '#type' => 'submit',
                            '#value' => $this->t('Remove'),
                            '#ajax' => [
                                'callback' => [get_class($this), 'updateWidgetCallback'],
                                'wrapper' => $detailsId,
                            ],
                            '#submit' => [[get_class($this), 'removeItemSubmit']],
                            '#name' => sprintf($namePattern, 'remove'),
                            '#limit_validation_errors' => [array_merge($fieldParents, [$this->fieldDefinition->getName()])],
                            '#attributes' => [
                                'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
                                'data-row-id' => $rowId,
                                'class' => ['remove-button'],
                            ],
                            '#access' => (bool) $this->getSetting('field_widget_remove'),
                        ],
                        'replace_button' => [
                            '#type' => 'submit',
                            '#value' => $this->t('Replace'),
                            '#ajax' => [
                                'callback' => [get_class($this), 'updateWidgetCallback'],
                                'wrapper' => $detailsId,
                            ],
                            '#submit' => [[get_class($this), 'removeItemSubmit']],
                            '#name' => sprintf($namePattern, 'replace'),
                            '#limit_validation_errors' => [array_merge($fieldParents, [$this->fieldDefinition->getName()])],
                            '#attributes' => [
                                'data-entity-id' => $entity->getEntityTypeId() . ':' . $entity->id(),
                                'data-row-id' => $rowId,
                                'class' => ['replace-button'],
                            ],
                            '#access' => $replaceButtonAccess,
                        ],
                        'edit_button' => [
                            '#type' => 'submit',
                            '#value' => $this->t('Edit'),
                            '#name' => sprintf($namePattern, 'edit_button'),
                            '#ajax' => [
                                'url' => Url::fromRoute(
                                    'entity_browser.edit_form', [
                                        'entity_type' => $entity->getEntityTypeId(),
                                        'entity' => $entity->id(),
                                    ]
                                ),
                                'options' => [
                                    'query' => [
                                        'details_id' => $detailsId,
                                    ],
                                ],
                            ],
                            '#attributes' => [
                                'class' => ['edit-button'],
                            ],
                            '#access' => $editButtonAccess,
                        ],
                    ];
                },
                $entities,
                empty($entities) ? [] : range(0, count($entities) - 1)
            ),
        ];
    }

    protected function formElementEntities(FieldItemListInterface $items, array $element, FormStateInterface $formState): array
    {
        $entities = parent::formElementEntities($items, $element, $formState);

        // Do one more check in case we are an inline entity form.
        if (empty($entities)) {
            $entities = $this->getEntitiesFromUserInput($items, $element, $formState);
        }

        return $entities;
    }

    private function getEntitiesFromUserInput(FieldItemListInterface $items,array $element, FormStateInterface $formState): array
    {
        // Highly experimental for a very specific use case of an inline entity form with media field and the
        // parent form does not pass validation.
        $userInput = $formState->getUserInput();
        $parents = array_merge($element['#field_parents'] ?? [], [$items->getName(), 'target_id']);
        $values = NestedArray::getValue($userInput, $parents);

        if (empty($values)) {
            return [];
        }

        if (!is_array($values)) {
            $values = [$values];
        }

        $entityType = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
        $entityStorage = $this->entityTypeManager->getStorage($entityType);

        return array_filter(array_map(static function(string $id) use ($entityStorage) {
            return $entityStorage->load(str_replace('media:', '', $id));
        }, $values));
    }
}
