<?php

namespace Drupal\wmmedia\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *   id = "wmmedia_media_image_extras",
 *   label = @Translation("Media image with extras"),
 *   description = @Translation("An entity field containing an image media entity reference with an extra title / description."),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_autocomplete",
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class MediaImageExtras extends EntityReferenceItem {

    /**
     * {@inheritdoc}
     */
    public static function defaultStorageSettings() {
        return [
                'target_type' => 'media',
            ] + parent::defaultStorageSettings();
    }

    /**
     * {@inheritdoc}
     */
    public static function defaultFieldSettings() {
        return [
                'title_field' => false,
                'title_field_required' => false,
                'description_field' => false,
                'description_field_required' => false,
            ] + parent::defaultFieldSettings();
    }

    /**
     * {@inheritdoc}
     */
    public static function getPreconfiguredOptions() {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function fieldSettingsForm(array $form, FormStateInterface $form_state)
    {
        // Get base form.
        $element = parent::fieldSettingsForm($form, $form_state);

        $settings = $this->getSettings();

        // Add title configuration options.
        $element['title_field'] = array(
            '#type' => 'checkbox',
            '#title' => t('Enable <em>Title</em> field'),
            '#default_value' => $settings['title_field'],
            '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image. Enabling this field is not recommended as it can cause problems with screen readers.'),
            '#weight' => 11,
        );
        $element['title_field_required'] = array(
            '#type' => 'checkbox',
            '#title' => t('<em>Title</em> field required'),
            '#default_value' => $settings['title_field_required'],
            '#weight' => 12,
            '#states' => array(
                'visible' => array(
                    ':input[name="settings[title_field]"]' => array('checked' => true),
                ),
            ),
        );

        $element['description_field'] = array(
            '#type' => 'checkbox',
            '#title' => t('Enable <em>Description</em> field'),
            '#default_value' => $settings['description_field'],
            '#description' => t('The description field allows users to enter a caption for the image.'),
            '#weight' => 13,
        );
        $element['description_field_required'] = array(
            '#type' => 'checkbox',
            '#title' => t('<em>Description</em> field required'),
            '#default_value' => $settings['description_field_required'],
            '#weight' => 14,
            '#states' => array(
                'visible' => array(
                    ':input[name="settings[description_field]"]' => array('checked' => true),
                ),
            ),
        );

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
        $properties = parent::propertyDefinitions($field_definition);

        $titleDefinition = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('Title'))
            ->setRequired(FALSE);
        $properties['title'] = $titleDefinition;

        $descriptionDefinition = DataDefinition::create('string')
            ->setLabel(new TranslatableMarkup('Description'))
            ->setRequired(FALSE);
        $properties['description'] = $descriptionDefinition;

        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public static function schema(FieldStorageDefinitionInterface $field_definition) {
        $schema = parent::schema($field_definition);
        $schema['columns']['title'] = array(
            'type' => 'varchar',
            'length' => 255,
        );
        $schema['columns']['description'] = array(
            'type' => 'text',
            'size' => 'big',
        );

        return $schema;
    }

    /**
     * @return \Drupal\file\Entity\File|null
     */
    public function getFile()
    {
        if (!$this->getMedia()) {
            return null;
        }

        $source = $this->getMedia()->getSource();
        $field = $source->getConfiguration()['source_field'] ?? '';

        if (!$field) {
            throw new \RuntimeException(sprintf(
                'MediaSource %s has no source_field configured.',
                get_class($source)
            ));
        }

        return $this->getMedia()->$field->entity;
    }

    /**
     * @return \Drupal\media\Entity\Media
     */
    public function getMedia()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->get('description')->getValue();
    }

    /**
     * @return string
     */
    public function getCopyright()
    {
        if (!$this->getMedia()) {
            return '';
        }

        return $this->getMedia()->get('field_copyright')->value;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->get('title')->getValue();
    }

    /**
     * @return string
     */
    public function getAlternate()
    {
        if (!$this->getMedia()) {
            return '';
        }

        return $this->getMedia()->get('field_alternate')->value;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        if (!$this->getMedia()) {
            return null;
        }

        return (int) $this->getMedia()->get('field_width')->value;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        if (!$this->getMedia()) {
            return null;
        }

        return (int) $this->getMedia()->get('field_height')->value;
    }
}
