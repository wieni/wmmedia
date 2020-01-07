<?php

namespace Drupal\wmmedia\Plugin\media\Source;

use Drupal\field\FieldConfigInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;

/**
 * Imgix entity media source.
 *
 * @see \Drupal\file\FileInterface
 *
 * @MediaSource(
 *     id = "imgix",
 *     label = @Translation("Imgix"),
 *     description = @Translation("Use Imgix image fields for reusable media."),
 *     allowed_field_types = {"imgix"},
 *     default_thumbnail_filename = "generic.png"
 * )
 */
class Imgix extends MediaSourceBase
{
    public function getMetadataAttributes(): array
    {
        return [];
    }

    public function getMetadata(MediaInterface $media, $attribute_name)
    {
        /** @var FileInterface $file */
        $file = $media->get($this->configuration['source_field'])->entity;

        // If the source field is not required, it may be empty.
        if (!$file) {
            return parent::getMetadata($media, $attribute_name);
        }

        switch ($attribute_name) {
            case 'default_name':
                return $file->getFilename();
            case 'thumbnail_uri':
                return $this->getThumbnail($file) ?: parent::getMetadata($media, $attribute_name);
            default:
                return parent::getMetadata($media, $attribute_name);
        }
    }

    public function createSourceField(MediaTypeInterface $type): self
    {
        /** @var FieldConfigInterface $field */
        $field = parent::createSourceField($type);

        // Reset the field to its default settings so that we don't inherit the
        // settings from the parent class' source field.
        $settings = $this->fieldTypeManager->getDefaultFieldSettings($field->getType());

        return $field->set('settings', $settings);
    }

    /**
     * Gets the thumbnail image URI based on a file entity.
     *
     * @param FileInterface $file
     *   A file entity.
     *
     * @return string|null
     *   File URI of the thumbnail image or NULL if there is no specific icon.
     */
    protected function getThumbnail(FileInterface $file): ?string
    {
        $icon_base = $this->configFactory->get('media.settings')->get('icon_base_uri');

        // We try to automatically use the most specific icon present in the
        // $icon_base directory, based on the MIME type. For instance, if an
        // icon file named "pdf.png" is present, it will be used if the file
        // matches this MIME type.
        $mimetype = $file->getMimeType();
        $mimetype = explode('/', $mimetype);

        $icon_names = [
            $mimetype[0] . '--' . $mimetype[1],
            $mimetype[1],
            $mimetype[0],
        ];

        foreach ($icon_names as $icon_name) {
            $thumbnail = $icon_base . '/' . $icon_name . '.png';
            if (is_file($thumbnail)) {
                return $thumbnail;
            }
        }

        return null;
    }
}
