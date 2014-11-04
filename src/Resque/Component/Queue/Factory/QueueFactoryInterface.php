<?php

namespace Resque\Component\Queue\Factory;

use Resque\Component\Queue\Model\QueueInterface;

/**
 * Resque QueueFactoryInterface
 *
 * Constructs queues
 */
interface QueueFactoryInterface
{
    /**
     * @param string $name The name of the queue.
     * @return QueueInterface The queue
     */
    public function createQueue($name);
}
