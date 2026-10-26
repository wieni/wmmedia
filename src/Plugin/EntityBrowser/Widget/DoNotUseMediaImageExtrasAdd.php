<?php

namespace Drupal\wmmedia\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_browser\Attribute\EntityBrowserWidget;
use Drupal\entity_browser\WidgetBase;

#[EntityBrowserWidget(
    id: 'wmmedia_media_image_add',
    label: new TranslatableMarkup('Media image upload (DO NOT USE)'),
    description: new TranslatableMarkup('Upload widget that will create media entities of the uploaded images.'),
    auto_select: false,
)]
class DoNotUseMediaImageExtrasAdd extends WidgetBase
{
    protected function prepareEntities(array $form, FormStateInterface $form_state)
    {
        return [];
    }
}
