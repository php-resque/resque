<?php

namespace Resque\Component\Queue\Model;

use Resque\Component\Job\Model\JobInterface;

/**
 * Resque Queue
 *
 * Defines the basic interface of a queue needed by a worker, or job creator.
 */
interface QueueInterface
{
    /**
     * Set name.
     *
     * @param string $name The name of the queue.
     */
    public function setName($name);

    /**
     * Get name.
     *
     * @return string The name of the queue.
     */
    public function getName();

    /**
     * Enqueue.
     *
     * Places a job at the end of a this queue.
     *
     * @param JobInterface $job The Job to enqueue.
     * @return bool True if successful, false otherwise.
     */
    public function enqueue(JobInterface $job);

    /**
     * Dequeue.
     *
     * Grabs a job off the front of this queue, and returns it.
     *
     * @return JobInterface|null The job from the queue, or null if no jobs.
     */
    public function dequeue();

    /**
     * Job count.
     *
     * Return the number of pending jobs in the queue
     *
     * @return int The size of the queue.
     */
    public function count();
}
