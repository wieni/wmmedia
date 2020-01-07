<?php

namespace Drupal\wmmedia\Event;

use Symfony\Component\EventDispatcher\Event;

class MediaWidgetRenderEvent extends Event
{
    /** @var int */
    private $targetId;
    /** @var array */
    private $render;

    public function __construct(int $targetId)
    {
        $this->targetId = $targetId;
    }

    public function getTarget(): int
    {
        return $this->targetId;
    }

    public function getPreview(): ?array
    {
        return $this->render;
    }

    public function setPreview(array $render): void
    {
        $this->render = $render;
    }
}
