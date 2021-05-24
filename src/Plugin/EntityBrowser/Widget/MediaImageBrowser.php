<?php

namespace Drupal\wmmedia\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\entity_browser\Form\EntityBrowserForm;
use Drupal\image\Entity\ImageStyle;
use Drupal\wmmedia\Service\FormOptions;
use Drupal\wmmedia\Service\ImageOverviewFormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @EntityBrowserWidget(
 *     id = "wmmedia_media_image_browser",
 *     label = @Translation("Media image browser"),
 *     provider = "wmmedia",
 *     description = @Translation("Image listings for media browser"),
 *     auto_select = TRUE
 * )
 */
class MediaImageBrowser extends MediaBrowserBase
{
    /** @var ImageOverviewFormBuilder */
    protected $overviewFormBuilder;

    public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition)
    {
        $instance = parent::create(
            $container,
            $configuration,
            $pluginId,
            $pluginDefinition
        );
        $instance->overviewFormBuilder = $container->get('wmmedia.image.form_builder');

        return $instance;
    }

    public function getForm(array &$originalForm, FormStateInterface $formState, array $additionalWidgetParameters)
    {
        $form = parent::getForm($originalForm, $formState, $additionalWidgetParameters);
        $formObject = $formState->getFormObject();

        if (!$formObject instanceof EntityBrowserForm) {
            return $form;
        }

        $cardinality = $formState->get(['entity_browser', 'validators', 'cardinality', 'cardinality']);
        $isMultiple = $cardinality > 1 || $cardinality === EntityBrowserElement::CARDINALITY_UNLIMITED;
        $options = FormOptions::createForBrowser()
            ->setMultiple($isMultiple);

        $this->overviewFormBuilder->setForm($form, $options, $this->configuration);
        $form['#attached']['library'][] = 'wmmedia/image_browser';

        return $form;
    }

    public function defaultConfiguration()
    {
        return [
            'image_style' => 'thumbnail',
        ] + parent::defaultConfiguration();
    }

    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['image_style'] = [
            '#type' => 'select',
            '#title' => $this->t('Image style'),
            '#default_value' => $this->configuration['preset'],
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

    public function submitConfigurationForm(array &$form, FormStateInterface $formState): void
    {
        $values = $formState->getValues()['table'][$this->uuid()]['form'];
        $this->configuration['submit_text'] = $values['submit_text'];
        $this->configuration['auto_select'] = $values['auto_select'];
        $this->configuration['image_style'] = $values['image_style'];
    }
}
