<?php

namespace Drupal\wmmedia\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\media\MediaInterface;
use Drupal\wmmedia\Form\MediaImageOverview;
use Drupal\wmmedia\Service\ImageJsonFormatter;
use Drupal\wmmedia\Service\ImageOverviewFormBuilder;
use Drupal\wmmedia\Service\UsageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class GalleryController implements ContainerInjectionInterface
{
    /** @var FormBuilderInterface */
    protected $formBuilder;
    /** @var ImageJsonFormatter */
    protected $imageJsonFormatter;
    /** @var ImageOverviewFormBuilder */
    protected $imageOverviewFormBuilder;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var UsageManager */
    protected $usageManager;

    /** @var int */
    protected $limit = 30;
    /** @var int */
    protected $page = 0;
    /** @var int */
    protected $total;

    public static function create(ContainerInterface $container)
    {
        $instance = new static();
        $instance->formBuilder = $container->get('form_builder');
        $instance->imageOverviewFormBuilder = $container->get('wmmedia.image.form_builder');
        $instance->imageJsonFormatter = $container->get('wmmedia.image.json_formatter');
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->usageManager = $container->get('wmmedia.usage');

        /* @var RequestStack $requestStack */
        $requestStack = $container->get('request_stack');
        $request = $requestStack->getCurrentRequest();
        $instance->page = $request && $request->get('page') ? $request->get('page') : $instance->page;
        $instance->limit = $request && $request->get('limit') ? $request->get('limit') : $instance->limit;
        $instance->total = $instance->getTotalMediaCount();

        return $instance;
    }

    public function show(): array
    {
        $form = $this->formBuilder->getForm(MediaImageOverview::class);

        $media = [
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
            'items' => $this->getMedia(),
        ];

        return [
            '#theme' => 'wmmedia_gallery',
            '#_data' => compact('form', 'media'),
        ];
    }

    public function get(): JsonResponse
    {
        return new JsonResponse([
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
            'pages' => ceil($this->total / $this->limit),
            'items' => $this->getMedia(),
        ]);
    }

    public function getMedia(): array
    {
        $items = $this->imageOverviewFormBuilder->getImages();
        $mediaIds = array_column($items, 'mid');

        // We load all media entities here, so it's done in a single query.
        // The EntityManager is smart enough to cache the entities, so we don't
        // have to refactor other parts of the code. They can keep doing a
        // separate ::load() call.
        $mediaEntities = $this->entityTypeManager
            ->getStorage('media')
            ->loadMultiple($mediaIds);

        // We call the UsageManager here, so it's done in a single query.
        // The UsageManager is smart enough to cache the entities, so we don't
        // have to refactor other parts of the code. They can keep doing a
        // separate ::hasUsage() call.
        $this->usageManager->performHasUsageCheck($mediaIds);

        // Load all associated files in a single query.
        $fileIds = array_values(array_filter(array_map(
            static function (MediaInterface $entity) {
                $sourceField = $entity->getSource()->getConfiguration()['source_field'];
                $file = $entity->{$sourceField}->getValue();
                if ($file && isset($file[0]['target_id'])) {
                    return $file[0]['target_id'];
                }
                return null;
            },
            $mediaEntities
        )));
        $this->entityTypeManager
            ->getStorage('file')
            ->loadMultiple($fileIds);

        return array_values(
            array_filter(
                array_map(function ($item) {
                    return $this->imageJsonFormatter->toJson($item);
                }, $items)
            )
        );
    }

    public function getTotalMediaCount(): int
    {
        return $this->imageOverviewFormBuilder->getImagesCount();
    }
}
