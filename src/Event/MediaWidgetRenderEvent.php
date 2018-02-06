<?php

namespace Drupal\wmmedia\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that fires when we're rendering mediaWidget
 */
class MediaWidgetRenderEvent extends Event
{
    const NAME = 'wmmedia.media_widget.render';

    /** @var string */
    private $targetId;

    /** @var array $render */
    private $render;

    /**
     * MediaWidgetRenderEvent constructor.
     * @param $targetId
     */
    public function __construct($targetId)
    {
        $this->targetId = $targetId;
    }

    /**
     * @return int
     */
    public function getTarget()
    {
        return $this->targetId;
    }

    /**
     * @return array
     */
    public function getPreview()
    {
        return $this->render;
    }

    /**
     * @param array $render
     */
    public function setPreview(array $render)
    {
        $this->render = $render;
    }
}
