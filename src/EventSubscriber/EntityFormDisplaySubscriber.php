<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EntityFormDisplaySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            HookEventDispatcherInterface::ENTITY_PRE_SAVE => 'onPreSave',
        ];
    }

    public function onPreSave(EntityPresaveEvent $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof EntityFormDisplay) {
            return;
        }

        $this->setDefaultEntityBrowser($entity);
    }

    protected function setDefaultEntityBrowser(EntityFormDisplay $entity)
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

        $entity->set('content', $content);
    }

    protected function getDefaultEntityBrowser(FieldConfig $fieldDefinition)
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
