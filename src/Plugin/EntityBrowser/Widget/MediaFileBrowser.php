<?php

namespace Drupal\wmmedia\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Form\EntityBrowserForm;
use Drupal\wmmedia\Service\FormOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @EntityBrowserWidget(
 *     id = "wmmedia_media_file_browser",
 *     label = @Translation("Media file browser"),
 *     provider = "wmmedia",
 *     description = @Translation("File listing for media browser"),
 *     auto_select = TRUE
 * )
 */
class MediaFileBrowser extends MediaBrowserBase
{

    /**
     * @var \Drupal\wmmedia\Service\FileOverviewFormBuilder;
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

        $instance->overviewFormBuilder = $container->get('wmmedia.file.form_builder');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(array &$originalForm, FormStateInterface $formState, array $additionalWidgetParameters)
    {
        $form = parent::getForm($originalForm, $formState, $additionalWidgetParameters);
        $this->overviewFormBuilder->setForm($form, FormOptions::createForBrowser());

        $buildInfo = $formState->getBuildInfo();
        $callbackObject = $buildInfo['callback_object'] ?? null;

        if (!$callbackObject instanceof EntityBrowserForm) {
            return $form;
        }

        $form['#attached']['library'][] = 'wmcustom/media_file_browser';

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(array &$element, array &$form, FormStateInterface $form_state)
    {
        $entities = $this->prepareEntities($form, $form_state);
        $this->selectEntities($entities, $form_state);
    }
}
