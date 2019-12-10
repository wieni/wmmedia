<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\file\Entity\File;
use Drupal\hook_event_dispatcher\Event\Entity\EntityDeleteEvent;
use Drupal\media\Entity\Media;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaDeleteSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents():array
    {
        return [
            'hook_event_dispatcher.entity.delete' => 'deleteFile',
        ];
    }

    public function deleteFile(EntityDeleteEvent $event): void
    {
        $entity = $event->getEntity();

        if (!$entity instanceof Media) {
            return;
        }

        $file = $entity->get('field_media_file')->entity;

        if (!$file instanceof File) {
            return;
        }

        $file->delete();
    }
}
