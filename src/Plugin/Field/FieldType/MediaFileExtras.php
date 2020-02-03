<?php

namespace Drupal\wmmedia\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;

/**
 * @FieldType(
 *     id = "wmmedia_media_file_extras",
 *     label = @Translation("Media file with extras"),
 *     description = @Translation("An entity field containing an file media entity reference with an extra title."),
 *     category = @Translation("Reference"),
 *     default_widget = "entity_reference_autocomplete",
 *     default_formatter = "entity_reference_label",
 *     list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class MediaFileExtras extends EntityReferenceItem
{
    public static function defaultStorageSettings()
    {
        return [
            'target_type' => 'media',
        ] + parent::defaultStorageSettings();
    }

    public static function defaultFieldSettings()
    {
        return [
            'title_field' => false,
            'title_field_required' => false,
        ] + parent::defaultFieldSettings();
    }

    public static function getPreconfiguredOptions()
    {
        return [];
    }

    public function fieldSettingsForm(array $form, FormStateInterface $form_state)
    {
        $element = parent::fieldSettingsForm($form, $form_state);
        $settings = $this->getSettings();

        $element['title_field'] = [
            '#type' => 'checkbox',
            '#title' => t('Enable <em>Title</em> field'),
            '#default_value' => $settings['title_field'],
            '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the file. Enabling this field is not recommended as it can cause problems with screen readers.'),
            '#weight' => 11,
        ];
        $element['title_field_required'] = [
            '#type' => 'checkbox',
            '#title' => t('<em>Title</em> field required'),
            '#default_value' => $settings['title_field_required'],
            '#weight' => 12,
            '#states' => [
                'visible' => [
                    ':input[name="settings[title_field]"]' => ['checked' => true],
                ],
            ],
        ];

        return $element;
    }

    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties = parent::propertyDefinitions($field_definition);

        $properties['title'] = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('Title'))
            ->setRequired(false);

        return $properties;
    }

    public static function schema(FieldStorageDefinitionInterface $field_definition)
    {
        $schema = parent::schema($field_definition);

        $schema['columns']['title'] = [
            'type' => 'varchar',
            'length' => 255,
        ];

        return $schema;
    }

    public function getFile(): ?FileInterface
    {
        if ($this->getMedia()) {
            $source = $this->getMedia()->getSource();
            $field = $source->getConfiguration()['source_field'] ?? '';

            if (!$field) {
                throw new \RuntimeException(sprintf(
                    'MediaSource %s has no source_field configured.',
                    get_class($source)
                ));
            }

            return $this->getMedia()->{$field}->entity;
        }

        return null;
    }

    public function getMedia(): ?MediaInterface
    {
        return $this->entity;
    }

    public function getTitle(): ?string
    {
        return $this->get('title')->getValue();
    }
}
