<?php

namespace Resque\Component\Core\Event;

interface EventDispatcherInterface
{
    /**
     * @param string $eventName The name of the event being dispatched.
     * @param $context
     */
    public function dispatch($eventName, $context);
}
