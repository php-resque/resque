<?php

namespace Resque\Event;

interface EventDispatcherInterface
{
    public function dispatch($event, $context);
}
