<?php

namespace Drupal\wmmedia\Install;

use Drupal\Core\Field\BaseFieldDefinition;

class MediaBaseFieldDefinitions
{

    public static bool $enabled = true;

    public static function getBaseFieldDefinitions(): array
    {
        if (self::$enabled === false) {
            /**
             * We need to be able to stop the module of registering the base
             * fields while we are migrating the data from the old fields to the
             * new fields.
             * @see \wmmedia_update_8009
             */
            return [];
        }

        $fields = [];
        $fields['field_width'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Width'))
            ->setCardinality(1)
            ->setTranslatable(false)
            ->setDescription(t('The width of the image in pixels.'))
            ->setSetting('unsigned', true)
            ->setSetting('size', 'normal')
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'integer',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', true)
            ->setDisplayConfigurable('view', true);

        $fields['field_height'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Height'))
            ->setCardinality(1)
            ->setTranslatable(false)
            ->setDescription(t('The height of the image in pixels.'))
            ->setSetting('unsigned', true)
            ->setSetting('size', 'normal')
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'integer',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', true)
            ->setDisplayConfigurable('view', true);

        $fields['field_description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Caption'))
            ->setCardinality(1)
            ->setTranslatable(true)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'text_default',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', true)
            ->setDisplayConfigurable('view', true);

        $fields['field_copyright'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Copyright'))
            ->setCardinality(1)
            ->setTranslatable(true)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'text_default',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', true)
            ->setDisplayConfigurable('view', true);

        $fields['field_alternate'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Alternate (alt) text'))
            ->setCardinality(1)
            ->setTranslatable(true)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', true)
            ->setDisplayConfigurable('view', true);

        return $fields;
    }

}
