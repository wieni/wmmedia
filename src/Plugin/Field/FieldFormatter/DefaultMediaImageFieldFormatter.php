<?php

namespace Drupal\wmmedia\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\imgix\ImgixManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @FieldFormatter(
 *     id = "wmmedia_media_image_default",
 *     label = @Translation("Image"),
 *     field_types = {
 *         "wmmedia_media_image_extras"
 *     }
 * )
 */
class DefaultMediaImageFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface
{
    /** @var ImgixManagerInterface */
    protected $imgixManager;

    public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition
    ) {
        $instance = new static(
            $plugin_id,
            $plugin_definition,
            $configuration['field_definition'],
            $configuration['settings'],
            $configuration['label'],
            $configuration['view_mode'],
            $configuration['third_party_settings']
        );
        $instance->imgixManager = $container->get('imgix.manager');

        return $instance;
    }

    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements = [];

        foreach ($items as $delta => $item) {
            $elements[$delta] = [
                '#theme' => 'wmmedia_image',
                '#field' => $item,
                '#preset' => $this->getSetting('preset'),
            ];
        }

        return $elements;
    }

    public function settingsForm(array $form, FormStateInterface $form_state)
    {
        $options = array_keys($this->imgixManager->getPresets());
        $build = [];

        $build['preset'] = [
            '#type' => 'select',
            '#options' => array_combine($options, $options),
            '#default_value' => $this->getSetting('preset'),
            '#required' => true,
        ];

        return $build;
    }

    public function settingsSummary()
    {
        $summary = [];

        $preset = $this->getSetting('preset');
        $presets = $this->imgixManager->getPresets();
        parse_str($presets[$preset]['query'], $params);

        $markup = [];
        foreach ($params as $key => $value) {
            $markup[] = $key . ': ' . $value;
        }

        $summary['summary']['#markup'] = new FormattableMarkup('@preset (@params)', [
            '@preset' => $preset,
            '@params' => trim(implode(', ', $markup)),
        ]);

        return $summary;
    }

    public static function defaultSettings()
    {
        return [
            'preset' => 'default',
        ];
    }
}
