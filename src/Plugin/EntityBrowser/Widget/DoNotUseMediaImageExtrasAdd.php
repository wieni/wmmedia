<?php

namespace Drupal\wmmedia\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;

/**
 * @EntityBrowserWidget(
 *     id = "wmmedia_media_image_add",
 *     label = @Translation("Media image upload (DO NOT USE)"),
 *     description = @Translation("Upload widget that will create media entities of the uploaded images."),
 *     auto_select = FALSE,
 * )
 */
class DoNotUseMediaImageExtrasAdd extends WidgetBase
{
    protected function prepareEntities(array $form, FormStateInterface $form_state)
    {
    }
}
