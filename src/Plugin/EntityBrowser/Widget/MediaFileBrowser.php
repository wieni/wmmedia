<?php

namespace Drupal\wmmedia\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_browser\Attribute\EntityBrowserWidget;
use Drupal\entity_browser\Form\EntityBrowserForm;
use Drupal\wmmedia\Service\FileOverviewFormBuilder;
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
#[EntityBrowserWidget(
    id: 'wmmedia_media_file_browser',
    label: new TranslatableMarkup('Media file browser'),
    description: new TranslatableMarkup('File listing for media browser'),
    auto_select: true,
    provider: 'wmmedia',
)]
class MediaFileBrowser extends MediaBrowserBase
{
    /** @var FileOverviewFormBuilder */
    protected $overviewFormBuilder;

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

    public function getForm(array &$originalForm, FormStateInterface $formState, array $additionalWidgetParameters)
    {
        $form = parent::getForm($originalForm, $formState, $additionalWidgetParameters);
        $formObject = $formState->getFormObject();

        if (!$formObject instanceof EntityBrowserForm) {
            return $form;
        }

        $this->overviewFormBuilder->setForm($form, FormOptions::createForBrowser());
        $form['#attached']['library'][] = 'wmmedia/file_browser';

        return $form;
    }
}
