<?php

namespace Drupal\wmmedia\Event;

use Symfony\Component\EventDispatcher\Event;

class MediaWidgetRenderEvent extends Event
{
    /** @var string */
    private $targetId;
    /** @var array */
    private $render;

    public function __construct($targetId)
    {
        $this->targetId = $targetId;
    }

    public function getTarget(): int
    {
        return $this->targetId;
    }

    public function getPreview(): array
    {
        return $this->render;
    }

    public function setPreview(array $render)
    {
        $this->render = $render;
    }
}
