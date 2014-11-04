<?php

namespace Resque\Component\Queue;

/**
 * Contains all events thrown in the queue component
 */
final class ResqueQueueEvents
{
    /*
     * The event listener receives a Resque\Component\RedisQueue\Event\QueueEvent instance.
     *
     * @var string
     */
    const REGISTERED = 'resque.queue.registered';

    /*
     * The event listener receives a Resque\Component\RedisQueue\Event\QueueEvent instance.
     *
     * @var string
     */
    const UNREGISTERED = 'resque.queue.unregistered';

    /*
     * The event listener receives a Resque\Component\RedisQueue\Event\QueueJobEvent instance.
     *
     * @var string
     */
    const PRE_PUSH = 'resque.queue.pre_push';

    /*
     * The event listener receives a Resque\Component\RedisQueue\Event\QueueJobEvent instance.
     *
     * @var string
     */
    const POST_PUSH = 'resque.queue.post_push';
}
