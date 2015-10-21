<?php

namespace Resque\Component\Queue\Storage;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Model\QueueInterface;

/**
 * QueueStorageInterface
 *
 * Allows the storage mechanism for a queue to be switched out. eg Redis, SPL, RabbitMQ.
 */
interface QueueStorageInterface
{
    /**
     * Enqueue.
     *
     * @param QueueInterface $queue
     * @param JobInterface $job The job to enqueue.
     * @return void
     */
    public function enqueue(QueueInterface $queue, JobInterface $job);

    /**
     * Pop.
     *
     * @param QueueInterface $queue
     * @return JobInterface|NULL JobInterface if items in the queue, NULL otherwise.
     */
    public function dequeue(QueueInterface $queue);

    /**
     * Remove.
     *
     * @param QueueInterface $queue
     * @param $filter @deprecated This should not be handled here.
     * @return mixed
     */
    public function remove(QueueInterface $queue, $filter = array());

    /**
     * Count.
     *
     * Return the number of pending jobs in the queue
     *
     * @param QueueInterface $queue
     * @return int The number it items in the queue.
     */
    public function count(QueueInterface $queue);
}
