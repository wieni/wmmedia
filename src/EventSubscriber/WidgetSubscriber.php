<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileInterface;
use Drupal\imgix\ImgixManagerInterface;
use Drupal\media\Entity\Media;
use Drupal\wmmedia\Event\MediaWidgetRenderEvent;
use Drupal\wmmedia\WmmediaEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WidgetSubscriber implements EventSubscriberInterface
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var ImgixManagerInterface */
    protected $imgixManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        ImgixManagerInterface $imgixManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->imgixManager = $imgixManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WmmediaEvents::MEDIA_WIDGET_RENDER => 'widgetRender',
        ];
    }

    public function widgetRender(MediaWidgetRenderEvent $event): void
    {
        $targetId = $event->getTarget();
        $element = false;

        if (!$targetId) {
            return;
        }

        /** @var Media $entity */
        $entity = $this->entityTypeManager->getStorage('media')->load($targetId);

        switch ($entity->bundle()) {
            case 'image':
                /** @var FileInterface $file */
                $file = $entity->get('field_media_imgix')->entity;

                if ($file) {
                    $element = [
                        '#theme' => 'imgix_image',
                        '#url' => $this->imgixManager->getImgixUrl(
                            $file,
                            [
                                'auto' => 'format',
                                'fit' => 'max',
                                'h' => 150,
                                'q' => 75,
                                'w' => 150,
                            ]
                        ),
                        '#weight' => -10,
                        '#title' => null,
                        '#caption' => null,
                        '#prefix' => '<a href="' . file_create_url($file->getFileUri()) . '" target="_blank">',
                        '#suffix' => '</a>',
                        '#attached' => ['library' => ['wmmedia/media.dialog']],
                    ];
                }
                break;
            case 'file':
                /** @var FileInterface $file */
                $file = $entity->get('field_media_file')->entity;

                if ($file) {
                    $element = [
                        '#markup' => '<a href="' . file_create_url($file->getFileUri()) . '" target="_blank">' . $file->label() . '</a>',
                    ];
                }
                break;
        }

        if ($element) {
            $event->setPreview($element);
        }
    }
}
