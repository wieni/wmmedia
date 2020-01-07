<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent;
use Drupal\wmmedia\Service\UsageManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UsageEntitySubscriber implements EventSubscriberInterface
{
    /** @var UsageManager */
    protected $usageManager;

    public function __construct(
        UsageManager $usageManager
    ) {
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
        $entity = $event->getEntity();
        $this->usageManager->track($entity);
    }

    public function clearUsage(BaseEntityEvent $event): void
    {
        $entity = $event->getEntity();
        $this->usageManager->clear($entity);
    }
}
