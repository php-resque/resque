<?php

namespace Resque\Component\Queue;

/**
 * Queue events
 *
 * Contains all events thrown in the queue component
 */
final class ResqueQueueEvents
{
    /**
     * The event listener receives a Resque\Component\Queue\Event\QueueEvent instance.
     *
     * @var string
     */
    const REGISTERED = 'resque.queue.registered';

    /**
     * The event listener receives a Resque\Component\Queue\Event\QueueEvent instance.
     *
     * @var string
     */
    const UNREGISTERED = 'resque.queue.unregistered';

    /**
     * The event listener receives a Resque\Component\Queue\Event\QueueJobEvent instance.
     *
     * @var string
     */
    const JOB_PUSH = 'resque.queue.job_push';

    /**
     * The event listener receives a Resque\Component\Queue\Event\QueueJobEvent instance.
     *
     * @var string
     */
    const JOB_PUSHED = 'resque.queue.job_pushed';

    /**
     * The event listener receives a Resque\Component\Queue\Event\QueueJobEvent instance.
     *
     * @var string
     */
    const JOB_POPPED = 'resque.queue.job_popped';
}
