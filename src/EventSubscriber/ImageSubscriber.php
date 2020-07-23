<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;

class ImageSubscriber
{
    public function onPreSave(EntityInterface $entity): void
    {
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
