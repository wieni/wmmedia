<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\file\FileInterface;
use Drupal\hook_event_dispatcher\Event\Entity\EntityDeleteEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaDeleteSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            HookEventDispatcherInterface::ENTITY_DELETE => 'deleteFile',
        ];
    }

    public function deleteFile(EntityDeleteEvent $event): void
    {
        $entity = $event->getEntity();

        if (!$entity instanceof MediaInterface) {
            return;
        }

        $file = $entity->get('field_media_file')->entity;

        if (!$file instanceof FileInterface) {
            return;
        }

        $file->delete();
    }
}
