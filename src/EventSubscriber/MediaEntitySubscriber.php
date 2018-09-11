<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;
use Drupal\hook_event_dispatcher\Event\Entity\EntityPredeleteEvent;
use Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\wmmedia\Service\MediaReferenceDiscovery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaEntitySubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    /** @var MediaReferenceDiscovery */
    protected $referenceDiscovery;

    public function __construct(
        MediaReferenceDiscovery $mediaReferenceDiscovery
    ) {
        $this->referenceDiscovery = $mediaReferenceDiscovery;
    }

    public static function getSubscribedEvents()
    {
        return [
            HookEventDispatcherInterface::ENTITY_PRE_SAVE => 'onPreSave',
            HookEventDispatcherInterface::ENTITY_PRE_DELETE => ['onPreDelete', 500],
        ];
    }

    public function onPreSave(EntityPresaveEvent $event)
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Media) {
            return;
        }

        $this->addWidthHeight($entity);
    }

    public function onPreDelete(EntityPredeleteEvent $event)
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Media) {
            return;
        }

        $this->removeReferences($entity);
    }

    protected function addWidthHeight(MediaInterface $entity)
    {
        if (!$entity->isNew() || $entity->bundle() !== 'image' || $entity->get('field_media_imgix')->isEmpty()) {
            return;
        }

        /** @var FileInterface $file */
        $file = $entity->get('field_media_imgix')->entity;
        $size = getimagesize($file->getFileUri());

        if (!isset($size[0]) || !isset($size[1])) {
            return;
        }

        $entity->set('field_width', $size[0]);
        $entity->set('field_height', $size[1]);
    }

    protected function removeReferences(MediaInterface $entity)
    {
        $usages = $this->referenceDiscovery->getUsages($entity);

        foreach ($usages as $fieldName => $entity) {
            /** @var $entity FieldableEntityInterface */
            $entity->set($fieldName, null);
            $entity->save();
        }

        if (!empty($usages)) {
            drupal_set_message(
                $this->formatPlural(count($usages), 'Removed 1 reference to image', 'Removed @count references to image')
            );
        }
    }
}