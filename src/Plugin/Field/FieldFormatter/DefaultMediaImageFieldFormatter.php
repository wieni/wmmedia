<?php

namespace Drupal\wmmedia\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * @FieldFormatter(
 *     id = "wmmedia_media_image_default",
 *     label = @Translation("Image"),
 *     field_types = {
 *         "wmmedia_media_image_extras"
 *     }
 * )
 */
class DefaultMediaImageFieldFormatter extends FormatterBase
{
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = [];

        foreach ($items as $delta => $item) {
            if (!$file = $item->getFile()) {
                continue;
            }

            $elements[$delta] = [
                '#theme' => 'image_style',
                '#style_name' => $this->getSetting('image_style'),
                '#uri' => $file->getFileUri(),
            ];
        }

        return $elements;
    }

    public function settingsForm(array $form, FormStateInterface $form_state)
    {
        $form['image_style'] = [
            '#type' => 'select',
            '#title' => $this->t('Image style'),
            '#default_value' => $this->settings['image_style'],
            '#options' => array_map(
                static function (ImageStyle $imageStyle) {
                    return $imageStyle->label();
                },
                ImageStyle::loadMultiple()
            ),
            '#required' => true,
        ];

        return $form;
    }

    public function settingsSummary()
    {
        $summary = [];

        if ($imageStyle = ImageStyle::load($this->getSetting('image_style'))) {
            $imageStyleLabel = $imageStyle->label();
        } else {
            $imageStyleLabel = 'Broken';
        }

        $summary['summary']['#markup'] = $this->t('Image style: @imageStyleLabel', [
            '@imageStyleLabel' => $imageStyleLabel,
        ]);

        return $summary;
    }

    public static function defaultSettings()
    {
        return [
            'image_style' => 'thumbnail',
        ];
    }
}
