<?php

namespace Drupal\wmmedia\Plugin\media\Source;

use Drupal\media\Plugin\media\Source\Image;

/**
 * Imgix entity media source.
 *
 * @deprecated Use Drupal\media\Plugin\media\Source\Image.
 *
 * @MediaSource(
 *     id = "imgix",
 *     label = @Translation("Imgix"),
 *     description = @Translation("Use Imgix image fields for reusable media."),
 *     allowed_field_types = {"imgix"},
 *     default_thumbnail_filename = "generic.png"
 * )
 */
class ImgixDoNotUse extends Image
{
}
