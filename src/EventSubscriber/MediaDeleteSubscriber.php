<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;

/**
 * called in @see wmmedia_entity_delete()
 */
class MediaDeleteSubscriber
{
    public function deleteFile(EntityInterface $entity): void
    {
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
