<?php

namespace Resque\Component\Core;

/**
 * Resque core events
 */
final class ResqueEvents
{
    /**
     * The PRE_FORK event is thrown just before a worker, or the foreman forks to run.
     *
     * The event listener receives no context.
     *
     * @event
     *
     * @var string
     */
    const PRE_FORK = 'resque.pre_fork';

    /**
     * The POST_FORK_PARENT event is thrown after a process forks, and the runtime is still the parent.
     *
     * The event listener receives no context.
     *
     * @event
     *
     * @var string
     */
    const POST_FORK_PARENT = 'resque.post_fork_parent';

    /**
     * The POST_FORK_CHILD event is thrown after a process forks and the runtime is now the child.
     *
     * The event listener receives no context.
     *
     * @event
     *
     * @var string
     */
    const POST_FORK_CHILD = 'resque.post_fork_child';
}
