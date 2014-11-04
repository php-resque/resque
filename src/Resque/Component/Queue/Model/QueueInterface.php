<?php

namespace Resque\Component\Queue\Model;

use Resque\Component\Job\Model\JobInterface;

/**
 * Resque QueueInterface
 *
 * Defines the basic interface of a RedisQueue needed by a worker.
 */
interface QueueInterface
{
    /**
     * @param string $name The name of the queue.
     * @return self
     */
    public function setName($name);

    /**
     * @return string The name of the queue.
     */
    public function getName();

    /**
     * Push a job to the end of a specific queue. If the queue does not
     * exist, then create it as well.
     *
     * @param JobInterface $job The Job to enqueue/push.
     * @return bool True if successful, false otherwise.
     */
    public function push(JobInterface $job);

    /**
     * Pop a job off the end of the specified queue, decode it and return it.
     *
     * @return JobInterface|null Decoded job from the queue, or null if no jobs.
     */
    public function pop();

    /**
     * Return the number of pending jobs in the queue
     *
     * @return int The size of the queue.
     */
    public function count();
}
