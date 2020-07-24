<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Url;
use Drupal\file\Entity\File;

trait RenderFileTrait
{
    protected function renderFile(File $file, string $name): array
    {
        // @see template_preprocess_file_link()
        $options = ['attributes' => []];
        $url = $file->createFileUrl(false);

        $mimeType = $file->getMimeType();
        $options['attributes']['type'] = $mimeType . '; length=' . $file->getSize();
        $options['attributes']['title'] = $file->getFilename();

        $classes = [
            'file',
            'file--mime-' . strtr($mimeType, ['/' => '-', '.' => '-']),
            'file--' . file_icon_class($mimeType),
        ];

        return [
            '#attached' => [
                'library' => ['file/drupal.file'],
            ],
            '#attributes' => [
                'class' => $classes,
                'target' => '_blank',
            ],
            '#cache' => [
                'contexts' => ['url.site'],
            ],
            '#title' => $name,
            '#type' => 'link',
            '#url' => Url::fromUri($url, $options),
        ];
    }
}
