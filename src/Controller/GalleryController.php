<?php

namespace Drupal\wmmedia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\wmmedia\Form\MediaImageOverview;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class GalleryController extends ControllerBase
{

    /**
     * @var \Drupal\wmmedia\Service\ImageJsonFormatter
     */
    protected $imageJsonFormatter;

    /**
     * @var \Drupal\wmmedia\Service\ImageOverviewFormBuilder
     */
    protected $imageOverviewFormBuilder;

    /**
     * @var int
     */
    protected $limit = 30;

    /**
     * @var int
     */
    protected $page = 0;

    /**
     * @var int
     */
    protected $total;

    /**
     * @inheritDoc
     */
    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->imageOverviewFormBuilder = $container->get('wmmedia.image.form_builder');
        $instance->imageJsonFormatter = $container->get('wmmedia.image.json_formatter');

        /* @var \Symfony\Component\HttpFoundation\RequestStack $requestStack */
        $requestStack = $container->get('request_stack');
        $request = $requestStack->getCurrentRequest();
        $instance->page = $request && $request->get('page') ? $request->get('page') : $instance->page;
        $instance->limit = $request && $request->get('limit') ? $request->get('limit') : $instance->limit;
        $instance->total = $instance->getTotalMediaCount();
        return $instance;
    }

    public function show(): array
    {
        $form = $this->formBuilder()->getForm(MediaImageOverview::class);

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

    public function getMedia()
    {
        $items = $this->imageOverviewFormBuilder->getImages();

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
