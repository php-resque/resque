<?php

namespace Resque\Component\Core\Event;

interface EventDispatcherInterface
{
    public function dispatch($event, $context);
}
