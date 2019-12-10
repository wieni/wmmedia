<?php

namespace Drupal\wmmedia\Plugin\CKEditorPlugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @CKEditorPlugin(
 *     id = "media_file_link",
 *     label = @Translation("Media file link")
 * )
 */
class MediaFileLink extends \Drupal\ckeditor\CKEditorPluginBase implements ContainerFactoryPluginInterface
{
    /**
     * @var \Drupal\Core\Extension\ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * @inheritDoc
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        $instance = new static($configuration, $plugin_id, $plugin_definition);
        $instance->moduleHandler = $container->get('module_handler');
        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(Editor $editor): array
    {
        return [
            'media_file_link_url' => Url::fromRoute('wmmedia.file.browser.editor')
                ->toString(TRUE)
                ->getGeneratedUrl(),
            'media_file_link_dialog_options' => [
                'dialogClass' => 'media-file-browser-editor',
                'resizable' => false,
                'title' => t('Add or select media'),
                'height' => 500,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getFile(): string
    {
        return sprintf(
            '%s/plugin.js',
            $this->getPath()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getLibraries(Editor $editor)
    {
        return [
            'core/jquery',
            'core/drupal',
            'core/drupal.ajax',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getButtons(): array
    {
        return [
            'media_file_link' => [
                'label' => t('Link media file'),
                'image' => sprintf('%s/icons/media_file_link.png', $this->getPath()),
            ],
        ];
    }

    /**
     * @return string
     */
    private function getPath(): string
    {
        return sprintf(
            '%s/js/ckeditor/plugins/media_file_link',
            $this->moduleHandler->getModule('wmmedia')->getPath()
        );
    }
}
