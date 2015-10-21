<?php

namespace Resque\Component\Worker;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Model\QueueInterface;

interface WorkerInterface
{
    /**
     * Get Id
     *
     * The id of worker is {hostname}:{pid}:{queues,...}
     *
     * @return string The id of this worker.
     */
    public function getId();

    /**
     * Set hostname
     *
     * @param string $hostname The name of the host the worker is/was running on.
     * @return $this
     */
    public function setHostname($hostname);

    /**
     * Get hostname
     *
     * @return string $hostname The name of the host the worker is/was running on.
     */
    public function getHostname();

    /**
     * Add queue
     *
     * @param QueueInterface $queue The queue to add to the worker.
     * @return $this
     */
    public function addQueue(QueueInterface $queue);

    /**
     * Get queues.
     *
     * Return an array containing all of the queues that this worker is using when searching for jobs.
     *
     * @return QueueInterface[] Array of queues this worker is dealing with.
     */
    public function getQueues();

    /**
     * Set PID
     *
     * @param int $pid
     * @return $this
     */
    public function setPid($pid);

    /**
     * Get process
     *
     * @return int The workers pid.
     */
    public function getPid();

    /**
     * Set current job.
     *
     * @param JobInterface|null $job The job being worked on, or null if the worker isn't processing a job anymore.
     * @throws ResqueRuntimeException when the current job is not cleared before setting a new one.
     * @return $this
     */
    public function setCurrentJob(JobInterface $job);

    /**
     * Get current job
     *
     * @return JobInterface|null The job this worker is currently processing, if one at all.
     */
    public function getCurrentJob();
}
