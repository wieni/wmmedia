<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\file\FileInterface;
use Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherEvents;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ImageSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            HookEventDispatcherEvents::ENTITY_PRE_SAVE => 'preSave',
        ];
    }
    /**
     * @param BaseEntityEvent $event
     */
    public function preSave(BaseEntityEvent $event)
    {
        $media = $event->getEntity();
        if (!$media instanceof Media) {
            return;
        }

        $this->addWidthHeight($media);
    }

    /**
     * @param MediaInterface $entity
     */
    private function addWidthHeight(MediaInterface $entity)
    {
        /** @var FileInterface $file */
        if ($entity->isNew() && $entity->bundle() == 'image' && !$entity->get('field_media_imgix')->isEmpty()) {
            $file = $entity->get('field_media_imgix')->entity;

            $size = getimagesize($file->getFileUri());
            if ($size[0] && $size[1]) {
                $entity->set('field_width', $size[0]);
                $entity->set('field_height', $size[1]);
            }
        }
    }
}
