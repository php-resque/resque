<?php

namespace Resque\Component\Queue\Storage;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Model\QueueInterface;

/**
 * QueueStorageInterface
 *
 * Allows the storage mechanism for a queue to be switched out. EG, Redis, SPL, RabbitMQ.
 */
interface QueueStorageInterface
{
    public function enqueue(QueueInterface $queue, JobInterface $job);

    public function dequeue(QueueInterface $queue);

    public function remove(QueueInterface $queue, $filter);

    /**
     * Count
     *
     * Return the number of pending jobs in the queue
     *
     * @return int The size of the queue.
     */
    public function count(QueueInterface $queue);
}
