<?php

namespace Resque;

use Resque\Job\JobInterface;

/**
 * Resque QueueInterface
 *
 * Defines the basic interface of a Queue needed by a worker.
 */
interface QueueInterface
{
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
     * @return Job|null Decoded job from the queue, or null if no jobs.
     */
    public function pop();
}
