<?php

namespace Drupal\wmmedia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\file\Entity\File;
use Drupal\imgix\ImgixManagerInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\wmmedia\Form\MediaContentFilterForm;
use Drupal\wmmedia\Service\MediaFilterService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GalleryController extends ControllerBase
{
    /** @var SessionInterface */
    protected $session;
    /** @var FormBuilderInterface */
    protected $formBuilder;
    /** @var ImgixManagerInterface */
    protected $imgixManager;
    /** @var MediaFilterService */
    protected $filterService;
    /** @var Request */
    protected $request;

    protected $page = 0;
    protected $limit = 30;
    protected $total;

    public function __construct(
        EntityTypeManager $entityTypeManager,
        SessionInterface $session,
        FormBuilderInterface $formBuilder,
        ImgixManagerInterface $imgixManager,
        MediaFilterService $filterService,
        RequestStack $requestStack,
        LanguageManagerInterface $languageManager
    ) {
        $this->entityTypeManager = $entityTypeManager;

        $this->session = $session;
        $this->formBuilder = $formBuilder;
        $this->imgixManager = $imgixManager;
        $this->filterService = $filterService;
        $this->request = $requestStack->getCurrentRequest();
        $this->languageManager = $languageManager;

        $this->page = $this->request->get('page') ?? $this->page;
        $this->limit = $this->request->get('limit') ?? $this->limit;
        $this->total = $this->getTotalMediaCount();
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('session'),
            $container->get('form_builder'),
            $container->get('imgix.manager'),
            $container->get('wmmedia.filter'),
            $container->get('request_stack'),
            $container->get('language_manager')
        );
    }

    public function show()
    {
        $form = $this->formBuilder->getForm(MediaContentFilterForm::class);

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

    public function get()
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
        $filter = $this->session->get(MediaContentFilterForm::getSessionVarName(), []);
        $items = $this->filterService->filter($filter, $this->limit);

        return array_values(
            array_filter(
                array_map(function ($item) {
                    return $this->toJson($item);
                }, $items)
            )
        );
    }

    public function getTotalMediaCount()
    {
        $filter = $this->session->get(MediaContentFilterForm::getSessionVarName(), []);
        return $this->filterService->mediaCount($filter);
    }

    private function toJson(array $item)
    {
        /** @var Media $entity */
        $entity = $this->getTranslatedMediaItem($item);

        if (!$entity || $entity->bundle() !== 'image') {
            return null;
        }

        $result = [
            'id' => $entity->id(),
            'label' => $entity->label(),
            'author' => $entity->getOwner()->getDisplayName(),
            'dateCreated' => (int) $entity->getCreatedTime(),
        ];

        if ($entity->hasField('field_copyright')) {
            $result['copyright'] = $entity->get('field_copyright')->value;
        }

        if ($entity->hasField('field_description')) {
            $result['caption'] = $entity->get('field_description')->value;
        }

        if ($entity->hasField('field_alternate')) {
            $result['alternate'] = $entity->get('field_alternate')->value;
        }

        if ($entity->hasField('field_height')) {
            $result['height'] = (int) $entity->get('field_height')->value;
        }

        if ($entity->hasField('field_width')) {
            $result['width'] = (int) $entity->get('field_width')->value;
        }

        /** @var File $file */
        if ($file = $entity->get('field_media_imgix')->entity) {
            $result['originalUrl'] = $this->imgixManager->getImgixUrl($file, []);
            $result['thumbUrl'] = $this->imgixManager->getImgixUrl($file, ['fit' => 'max', 'h' => 250]);
            $result['largeUrl'] = $this->imgixManager->getImgixUrl($file, ['fit' => 'max', 'h' => 1200]);
            $result['size'] = format_size($file->getSize());
        }

        $operations = $this->entityTypeManager
            ->getListBuilder('media')
            ->getOperations($entity);

        $result['operations'] = array_map(
            function (array $operation, string $key) {
                $operation['key'] = $key;
                $operation['url'] = $operation['url']->toString();

                return $operation;
            },
            array_values($operations),
            array_keys($operations)
        );

        return $result;
    }

    private function getTranslatedMediaItem($row)
    {
        $langcode = $this->languageManager
            ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
            ->getId();

        $entity = $this->entityTypeManager
            ->getStorage('media')
            ->load($row['mid']);

        if (!$entity instanceof MediaInterface) {
            return null;
        }

        if ($entity->hasTranslation($langcode)) {
            return $entity->getTranslation($langcode);
        }

        return $entity;
    }
}
