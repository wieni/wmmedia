<?php

namespace Drupal\wmmedia\Plugin\Filter;

use DOMElement;
use DOMXPath;
use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Filter(
 *     id = "wmmedia_file_link",
 *     title = @Translation("Process links to media entities."),
 *     type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *     weight = -10
 * )
 */
class MediaFileLinkFilter extends FilterBase implements ContainerFactoryPluginInterface
{

    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition)
    {
        $instance = new static(
            $configuration,
            $pluginId,
            $pluginDefinition
        );
        $instance->entityTypeManager = $container->get('entity_type.manager');
        return $instance;
    }

    public function process($text, $langcode): FilterProcessResult
    {
        $result = new FilterProcessResult($text);
        $dom = Html::load($text);
        $xpath = new DOMXPath($dom);
        $storage = $this->entityTypeManager->getStorage('media');

        foreach ($xpath->query('//a[@data-media-file-link]') as $element) {
            /* @var \DOMElement $element */
            $mid = $element->getAttribute('data-media-file-link');

            $media = $storage->load($mid);

            if (!$media instanceof Media) {
                $this->stripTag($element);
                continue;
            }

            $file = $media->get('field_media_file')->entity;

            if (!$file instanceof File) {
                $this->stripTag($element);
                continue;
            }

            $url = $file->createFileUrl(FALSE);

            $mimeType = $file->getMimeType();

            $element->setAttribute('href', $url);
            $element->setAttribute('target', '_blank');
            $element->setAttribute('type', $mimeType . '; length=' . $file->getSize());
            $element->setAttribute('title', $media->label());
            $element->removeAttribute('data-media-file-link');
        }

        $result->setProcessedText(Html::serialize($dom));

        return $result;
    }

    private function stripTag(DOMElement $element)
    {
        $string = new \DOMText($element->textContent);
        $element->parentNode->replaceChild($string, $element);
    }
}
