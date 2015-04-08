<?php

namespace Resque\Component\Queue\Registry;

use Resque\Component\Queue\Model\QueueInterface;

/**
 * QueueStorageInterface
 *
 * Allows the storage mechanism for a queue to be switched out.
 */
interface QueueStorageInterface
{
    public function push(QueueInterface $queue);

    public function pop(QueueInterface $queue);

    public function remove(QueueInterface $queue, $filter);

    /**
     * Return the number of pending jobs in the queue
     *
     * @return int The size of the queue.
     */
    public function count(QueueInterface $queue);
}
