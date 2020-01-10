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

        $configuration = $entity->getSource()->getConfiguration();

        if (!isset($configuration['source_field'])) {
            return;
        }

        $source = $entity->get($configuration['source_field'])->entity;

        if (!$source instanceof FileInterface) {
            return;
        }

        $source->delete();
    }
}
