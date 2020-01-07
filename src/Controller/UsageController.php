<?php

namespace Drupal\wmmedia\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\MediaInterface;
use Drupal\wmmedia\Service\UsageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UsageController implements ContainerInjectionInterface
{
    use StringTranslationTrait;

    /** @var UsageManager */
    protected $usage;

    public static function create(ContainerInterface $container)
    {
        $instance = new static();
        $instance->usage = $container->get('wmmedia.usage');

        return $instance;
    }

    public function overview(MediaInterface $media): array
    {
        return $this->usage->getUsageAsTable($media);
    }

    public function title(MediaInterface $media): string
    {
        return $this->t('Media usage for :media', [':media' => $media->label()]);
    }
}
