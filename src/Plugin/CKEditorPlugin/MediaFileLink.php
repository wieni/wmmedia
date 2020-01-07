<?php

namespace Drupal\wmmedia\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
class MediaFileLink extends CKEditorPluginBase implements ContainerFactoryPluginInterface
{
    /** @var ModuleHandlerInterface */
    protected $moduleHandler;

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        $instance = new static($configuration, $plugin_id, $plugin_definition);
        $instance->moduleHandler = $container->get('module_handler');

        return $instance;
    }

    public function getConfig(Editor $editor): array
    {
        return [
            'media_file_link_url' => Url::fromRoute('wmmedia.file.browser.editor')
                ->toString(true)
                ->getGeneratedUrl(),
            'media_file_link_dialog_options' => [
                'dialogClass' => 'media-file-browser-editor',
                'resizable' => false,
                'title' => t('Add or select media'),
                'height' => 500,
            ],
        ];
    }

    public function getFile(): string
    {
        return sprintf(
            '%s/plugin.js',
            $this->getPath()
        );
    }

    public function getLibraries(Editor $editor)
    {
        return [
            'core/jquery',
            'core/drupal',
            'core/drupal.ajax',
        ];
    }

    public function getButtons(): array
    {
        return [
            'media_file_link' => [
                'label' => t('Link media file'),
                'image' => sprintf('%s/icons/media_file_link.png', $this->getPath()),
            ],
        ];
    }

    private function getPath(): string
    {
        return sprintf(
            '%s/js/ckeditor/plugins/media_file_link',
            $this->moduleHandler->getModule('wmmedia')->getPath()
        );
    }
}
