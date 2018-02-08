<?php

namespace Drupal\wmmedia\Controller;

use Drupal\Core\Url;
use Drupal\wmcontroller\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\imgix\ImgixManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\wmmedia\Form\MediaContentFilterForm;
use Drupal\wmmedia\Service\MediaFilterService;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
    /** @var MediaFilterService */
    protected $filterService;

    public function __construct(
        EntityTypeManager $etm,
        SessionInterface $session,
        FormBuilderInterface $formBuilder,
        ImgixManagerInterface $imgixManager,
        MediaFilterService $filterService
    ) {
        $this->mediaStorage = $etm->getStorage('media');

        $this->session = $session;
        $this->formBuilder = $formBuilder;
        $this->imgixManager = $imgixManager;
        $this->filterService = $filterService;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('session'),
            $container->get('form_builder'),
            $container->get('imgix.manager'),
            $container->get('wmmedia.filter')
        );
    }

    public function show()
    {
        $filter = $this->session->get(
            MediaContentFilterForm::getSessionVarName(),
            []
        );

        $form = $this->formBuilder->getForm(MediaContentFilterForm::class);

        $media = array_map(function ($item) {
            /** @var \Drupal\media\Entity\Media $entity */
            $entity = $this->getTranslatedMedia($item);

            if (!$entity || $entity->bundle() !== 'image') {
                return null;
            }

            /** @var \Drupal\file\Entity\File $file */
            $file = $entity->get('field_media_imgix')->entity;
            $thumb = $file ? $this->imgixManager->getImgixUrl($file, ['fit' => 'max', 'h' => 250]) : null;
            $large = $file ? $this->imgixManager->getImgixUrl($file, ['fit' => 'max', 'w' => 1200]) : null;
            $original = $file ? $this->imgixManager->getImgixUrl($file, []) : null;
            $edit = Url::fromRoute('entity.media.edit_form', ['media' => $entity->id()])->toString();
            $delete = Url::fromRoute('entity.media.delete_form', ['media' => $entity->id()])->toString();

            return [
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

        }, $this->filterService->filter($filter, 30));

        $media = ['items' => array_values(array_filter($media))];

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

    private function getTranslatedMedia($row)
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
