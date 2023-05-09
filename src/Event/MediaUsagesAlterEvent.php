<?php

namespace Drupal\wmmedia\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\FieldableEntityInterface;

class MediaUsagesAlterEvent extends Event
{
    /** @var FieldableEntityInterface[] */
    protected $usages;

    public function __construct(array &$usages)
    {
        $this->usages = &$usages;
    }

    /**
     * Get the usages by reference.
     *
     * @return FieldableEntityInterface[]
     *   The usages.
     */
    public function &getUsages(): array
    {
        return $this->usages;
    }
}
