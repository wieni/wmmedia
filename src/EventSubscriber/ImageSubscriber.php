<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\file\FileInterface;
use Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ImageSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            HookEventDispatcherInterface::ENTITY_PRE_SAVE => 'onPreSave',
        ];
    }

    public function onPreSave(EntityPresaveEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Media) {
            return;
        }

        $this->addWidthHeight($entity);
    }

    protected function addWidthHeight(MediaInterface $entity): void
    {
        if (!$entity->isNew() || $entity->bundle() !== 'image' || $entity->get('field_media_imgix')->isEmpty()) {
            return;
        }

        /** @var FileInterface $file */
        $file = $entity->get('field_media_imgix')->entity;
        $size = @getimagesize($file->getFileUri());

        if (!isset($size[0], $size[1])) {
            return;
        }

        $entity->set('field_width', $size[0]);
        $entity->set('field_height', $size[1]);
    }
}
