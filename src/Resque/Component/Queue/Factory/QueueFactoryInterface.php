<?php

namespace Resque\Component\Queue\Factory;

use Resque\Component\Queue\Model\QueueInterface;

/**
 * Queue factory
 *
 * Constructs queues.
 */
interface QueueFactoryInterface
{
    /**
     * Create queue.
     *
     * @param string $name The name of the queue.
     * @return QueueInterface The queue.
     */
    public function createQueue($name);
}
