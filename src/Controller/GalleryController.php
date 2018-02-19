<?php

namespace Drupal\wmcustom\Controller\Admin;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\imgix\ImgixManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\wmcustom\Form\MediaContentFilterForm;
use Drupal\wmcustom\Service\Admin\ContentFilter;
use Drupal\wmcontroller\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GalleryController extends ControllerBase
{
    /** @var EntityStorageInterface */
    protected $mediaStorage;
    /** @var SessionInterface */
    protected $session;
    /** @var FormBuilderInterface */
    protected $formBuilder;
    /** @var ImgixManagerInterface */
    protected $imgixManager;
    /** @var ContentFilter */
    protected $filter;
    /** @var CurrentRouteMatch */
    protected $currentRouteMatch;
    /** @var Request */
    protected $request;

    protected $page = 0;
    protected $limit = 30;

    public function __construct(
        EntityTypeManager $etm,
        SessionInterface $session,
        FormBuilderInterface $formBuilder,
        ImgixManagerInterface $imgixManager,
        ContentFilter $filter,
        CurrentRouteMatch $currentRouteMatch,
        RequestStack $requestStack
    ) {
        $this->mediaStorage = $etm->getStorage('media');

        $this->session = $session;
        $this->formBuilder = $formBuilder;
        $this->imgixManager = $imgixManager;
        $this->filter = $filter;
        $this->currentRouteMatch = $currentRouteMatch;
        $this->request = $requestStack->getCurrentRequest();

        $this->page = $this->request->get('page') ?? $this->page;
        $this->limit = $this->request->get('limit') ?? $this->limit;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('session'),
            $container->get('form_builder'),
            $container->get('imgix.manager'),
            $container->get('wmcustom.content.filter'),
            $container->get('current_route_match'),
            $container->get('request_stack')
        );
    }

    public function show()
    {
        $form = $this->formBuilder->getForm(MediaContentFilterForm::class);

        $media = [
            'page' => $this->page,
            'limit' => $this->limit,
            'items' => $this->getMedia(),
        ];

        $actions = [
            'Add image' => Url::fromRoute(
                'entity.media.add_form',
                [
                    'entity_type_id' => 'media',
                    'bundle_parameter' => 'media_type',
                    'media_type' => 'image',
                ]
            )->setAbsolute(true)->toString()
        ];

        return $this->view('wmmedia.gallery', compact('actions', 'form', 'media'));
    }

    public function get()
    {
        $total = $this->getTotalMediaCount();

        return new JsonResponse([
            'page' => $this->page,
            'limit' => $this->limit,
            'pages' => ceil($total / $this->limit),
            'total' => $total,
            'items' => $this->getMedia(),
        ]);
    }

    public function getMedia()
    {
        $filter = $this->session->get(MediaContentFilterForm::getSessionVarName(), []);
        $items = $this->filterService->media($filter, $this->limit);

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
        /** @var \Drupal\media\Entity\Media $entity */
        $entity = $this->getTranslatedMediaItem($item);

        if (!$entity || $entity->bundle() !== 'image') {
            return null;
        }

        /** @var \Drupal\file\Entity\File $file */
        $file = $entity->get('field_media_imgix')->entity;
        $thumb = $file ? $this->imgixManager->getImgixUrl($file, ['fit' => 'max', 'h' => 250]) : null;
        $large = $file ? $this->imgixManager->getImgixUrl($file, ['fit' => 'max', 'w' => 1200]) : null;
        $original = $file ? $this->imgixManager->getImgixUrl($file, []) : null;
        $edit = Url::fromRoute('entity.media.edit_form', ['media' => $entity->id()])->toString();
        $delete = Url::fromRoute('entity.media.delete_form', ['media' => $entity->id()])
            ->setOption('query', ['destination' => $this->currentRouteMatch->getRouteObject()->getPath()])
            ->toString();

        return [
            'id' => $entity->id(),
            'label' => $entity->label(),
            'author' => $entity->getOwner()->getDisplayName(),
            'copyright' => $entity->get('field_copyright')->value,
            'caption' => $entity->get('field_description')->value,
            'alternate' => $entity->get('field_alternate')->value,
            'height' => (int) $entity->get('field_height')->value,
            'width' => (int) $entity->get('field_width')->value,
            'originalUrl' => $original,
            'thumbUrl' => $thumb,
            'largeUrl' => $large,
            'editUrl' => $edit,
            'deleteUrl' => $delete,
            'size' => format_size($file->getSize()),
            'dateCreated' => $entity->getCreatedTime(),
        ];
    }

    private function getOperations(MediaInterface $media, $destination = '')
    {
        $opts = [];
        if ($destination) {
            $opts['query']['destination'] = $destination;
        }

        return [
            'edit' => [
                'title' => 'Edit',
                'url' => $media->toUrl('edit-form', $opts),
            ],
//            'delete' => [
//                'title' => 'Delete',
//                'url' => $term->toUrl('delete-form', $opts),
//            ],
        ];
    }

    private function getTranslatedMediaItem($row)
    {
        $mid = $row['mid'];
        $langCode = $row['langcode'] ?? '';
        /** @var NodeInterface $node */
        $entity = $this->mediaStorage->load($mid);

        if ($langCode && $entity->hasTranslation($langCode)) {
            return $entity->getTranslation($langCode);
        }

        return null;
    }
}
