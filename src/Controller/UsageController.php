<?php

namespace Drupal\wmmedia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UsageController extends ControllerBase
{

    /**
     * @var \Drupal\wmmedia\Service\UsageManager
     */
    protected $usage;

    public static function create(ContainerInterface $container): ControllerBase
    {
        $instance = parent::create($container);
        $instance->usage = $container->get('wmmedia.usage');
        return $instance;
    }

    public function overview(Media $media): array
    {
        return $this->usage->getUsageAsTable($media);
    }

    public function title(Media $media): string
    {
        return $this->t('Media usage for :media', [':media' => $media->label()]);
    }
}
