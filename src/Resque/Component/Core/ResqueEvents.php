<?php

namespace Resque\Component\core;

/**
 * Resque core events
 */
final class ResqueEvents
{
    /**
     * The event listener receives a Resque\Component\Core\Event\GenericEvent instance.
     *
     * @var string
     */
    const BEFORE_FORK = 'resque.before_fork';
}
