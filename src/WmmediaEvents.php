<?php

namespace Drupal\wmmedia;

final class WmmediaEvents
{
    /**
     * Will be triggered after collecting the usages of a
     * media entity in the MediaReferenceDiscovery service
     *
     * The event object is an instance of
     * @uses \Drupal\wmmedia\Event\MediaUsagesAlterEvent
     */
    public const MEDIA_USAGES_ALTER = 'wmmedia.media_usages.alter';
}
