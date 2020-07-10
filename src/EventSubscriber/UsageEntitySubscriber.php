<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent;
use Drupal\wmmedia\Service\UsageManager;
use Drupal\wmmedia\Service\UsageRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UsageEntitySubscriber implements EventSubscriberInterface
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

    public static function getSubscribedEvents(): array
    {
        return [
            'hook_event_dispatcher.entity.insert' => 'trackUsage',
            'hook_event_dispatcher.entity.update' => 'trackUsage',
            'hook_event_dispatcher.entity.delete' => 'clearUsage',
        ];
    }

    public function trackUsage(BaseEntityEvent $event): void
    {
        if (!$this->isTableInstalled()) {
            return;
        }

        $entity = $event->getEntity();
        $this->usageManager->track($entity);
    }

    public function clearUsage(BaseEntityEvent $event): void
    {
        if (!$this->isTableInstalled()) {
            return;
        }

        $entity = $event->getEntity();
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
