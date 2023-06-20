<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * called in @see wmmedia_entity_presave()
 */
class EntityFormDisplaySubscriber
{
    public function onPreSave(EntityInterface $entity): void
    {
        if (!$entity instanceof EntityFormDisplay) {
            return;
        }

        $this->setDefaultEntityBrowser($entity);
    }

    protected function setDefaultEntityBrowser(EntityFormDisplay $entity): void
    {
        $widgets = $entity->get('widgets');
        $content = $entity->get('content') ?? [];

        if (!isset($widgets['wmmedia_media_widget'])) {
            return;
        }

        foreach ($content as $fieldName => &$widget) {
            if (
                !isset($widget['type'])
                || $widget['type'] !== 'wmmedia_media_widget'
                || !empty($widget['settings']['entity_browser'])
            ) {
                continue;
            }

            /** @var FieldConfig $fieldDefinition */
            $fieldDefinition = $entity->get('fieldDefinitions')[$fieldName];

            $widget['settings']['entity_browser'] = $this->getDefaultEntityBrowser($fieldDefinition);
        }
        unset($widget);

        $entity->set('content', $content);
    }

    protected function getDefaultEntityBrowser(FieldConfig $fieldDefinition): ?string
    {
        switch ($fieldDefinition->get('field_type')) {
            case 'wmmedia_media_image_extras':
                return 'images';
            case 'wmmedia_media_file_extras':
            default:
                return 'files';
        }
    }
}
