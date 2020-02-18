<?php

namespace Drupal\wmmedia\Access;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;

class MediaFileAccessCheck implements AccessInterface
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
    }

    public function access(): AccessResultInterface
    {
        $mediaType = $this->entityTypeManager
            ->getStorage('media_type')
            ->load('file');

        return AccessResult::allowedIf((bool) $mediaType)
            ->addCacheableDependency($mediaType);
    }
}
