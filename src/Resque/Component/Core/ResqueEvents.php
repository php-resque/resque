<?php

namespace Resque\Component\Core;

/**
 * Resque core events
 */
final class ResqueEvents
{
    /**
     * The event listener receives no context.
     *
     * @var string
     */
    const BEFORE_FORK = 'resque.before_fork';
}
