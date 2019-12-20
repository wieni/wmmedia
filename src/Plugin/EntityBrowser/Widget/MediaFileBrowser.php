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

        $formObject = $formState->getFormObject();

        if (!$formObject instanceof EntityBrowserForm) {
            return $form;
        }

        $this->overviewFormBuilder->setForm($form, FormOptions::createForBrowser());
        $form['#attached']['library'][] = 'wmmedia/browser';
        $form['#attached']['library'][] = 'wmmedia/file_browser';

        return $form;
    }
}
