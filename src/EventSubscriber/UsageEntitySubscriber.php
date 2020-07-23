<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\wmmedia\Service\UsageManager;
use Drupal\wmmedia\Service\UsageRepository;

class UsageEntitySubscriber
{
    /** @var Connection */
    protected $database;
    /** @var UsageManager */
    protected $usageManager;

    public function __construct(
        Connection $database,
        UsageManager $usageManager
    ) {
        $this->database = $database;
        $this->usageManager = $usageManager;
    }

    public function trackUsage(EntityInterface $entity): void
    {
        if (!$this->isTableInstalled()) {
            return;
        }

        $this->usageManager->track($entity);
    }

    public function clearUsage(EntityInterface $entity): void
    {
        if (!$this->isTableInstalled()) {
            return;
        }

        $this->usageManager->clear($entity);
    }

    /**
     * Prevent errors when entities are deleted during update
     * hooks before the wmmedia_usage schema is installed.
     */
    protected function isTableInstalled(): bool
    {
        return $this->database->schema()
            ->tableExists(UsageRepository::TABLE);
    }
}
