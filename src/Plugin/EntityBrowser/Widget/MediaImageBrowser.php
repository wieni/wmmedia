<?php

namespace Drupal\wmmedia\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Form\EntityBrowserForm;
use Drupal\wmmedia\Service\FormOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @EntityBrowserWidget(
 *   id = "wmmedia_media_image_browser",
 *   label = @Translation("Media image browser"),
 *   provider = "wmmedia",
 *   description = @Translation("Image listings for media browser"),
 *   auto_select = TRUE
 * )
 */
class MediaImageBrowser extends MediaBrowserBase
{

    /**
     * @var \Drupal\imgix\ImgixManagerInterface
     */
    protected $imgixManager;

    /**
     * @var \Drupal\wmmedia\Service\ImageOverviewFormBuilder
     */
    protected $overviewFormBuilder;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition)
    {
        $instance = parent::create(
            $container,
            $configuration,
            $pluginId,
            $pluginDefinition
        );

        $instance->overviewFormBuilder = $container->get('wmmedia.image.form_builder');
        $instance->imgixManager = $container->get('imgix.manager');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(array &$originalForm, FormStateInterface $formState, array $additionalWidgetParameters)
    {
        $form = parent::getForm($originalForm, $formState, $additionalWidgetParameters);

        $formObject = $formState->getFormObject();

        if (!$formObject instanceof EntityBrowserForm) {
            return $form;
        }

        $this->overviewFormBuilder->setForm($form, FormOptions::createForBrowser(), $this->configuration);
        $form['#attached']['library'][] = 'wmmedia/browser';
        $form['#attached']['library'][] = 'wmmedia/image_browser';

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration()
    {
        return [
            'preset' => null,
        ] + parent::defaultConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $options = [];
        foreach ($this->imgixManager->getPresets() as $preset) {
            $options[$preset['key']] = $preset['key'];
        }

        $form['preset'] = [
            '#type' => 'select',
            '#title' => $this->t('Imgix preset'),
            '#default_value' => $this->configuration['preset'],
            '#options' => $options,
            '#empty_option' => $this->t('- Select a preset -'),
            '#required' => true,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $formState): void
    {
        $values = $formState->getValues()['table'][$this->uuid()]['form'];
        $this->configuration['submit_text'] = $values['submit_text'];
        $this->configuration['auto_select'] = $values['auto_select'];
        $this->configuration['preset'] = $values['preset'];
    }
}