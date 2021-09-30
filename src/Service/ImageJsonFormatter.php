<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\MediaInterface;

class ImageJsonFormatter
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var LanguageManagerInterface */
    protected $languageManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        LanguageManagerInterface $languageManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->languageManager = $languageManager;
    }

    public function toJson(array $item): ?array
    {
        $entity = $this->getTranslatedMediaItem($item);

        if (!$entity) {
            return null;
        }

        $result = [
            'id' => $entity->id(),
            'label' => $entity->label(),
            'author' => '',
            'dateCreated' => (int) $entity->getCreatedTime(),
        ];

        if ($owner = $entity->getOwner()) {
            $result['author'] = $owner->getDisplayName();
        }

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

        $sourceField = $entity->getSource()->getConfiguration()['source_field'];
        $file = $entity->{$sourceField}->entity;

        if ($file instanceof FileInterface) {
            $result['originalUrl'] = $file->createFileUrl(false);
            $result['thumbUrl'] = $this->getImageUrl($file, 'wmmedia_gallery_thumb');
            $result['largeUrl'] = $this->getImageUrl($file, 'wmmedia_gallery_large');
            $result['size'] = format_size($file->getSize());
        }

        $operations = $this->entityTypeManager
            ->getListBuilder('media')
            ->getOperations($entity);

        $result['operations'] = array_map(
            static function (array $operation, string $key) {
                $operation['key'] = $key;
                $operation['url'] = $operation['url']->toString();

                return $operation;
            },
            array_values($operations),
            array_keys($operations)
        );

        return $result;
    }

    protected function getImageUrl(FileInterface $file, string $imageStyleId): ?string
    {
        $path = $file->getFileUri();

        if (!$imageStyle = ImageStyle::load($imageStyleId)) {
            return null;
        }

        if (!$imageStyle->supportsUri($path)) {
            return null;
        }

        return $imageStyle->buildUrl($path);
    }

    private function getTranslatedMediaItem($row): ?MediaInterface
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
