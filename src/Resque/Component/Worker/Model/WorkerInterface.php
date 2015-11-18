<?php

namespace Resque\Component\Worker\Model;

use DateTime;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Model\QueueInterface;

interface WorkerInterface
{
    /**
     * Get Id.
     *
     * The Id of worker is {hostname}:{pid}:{queues,...}
     *
     * @return string The id of this worker.
     */
    public function getId();

    /**
     * Get hostname.
     *
     * @return string $hostname The name of the host the worker is running on.
     */
    public function getHostname();

    /**
     * Get queues.
     *
     * Returns an array containing all of the queues that this worker is using when searching for jobs.
     *
     * @return QueueInterface[] Array of queues this worker is dealing with.
     */
    public function getAssignedQueues();

    /**
     * Get PID.
     *
     * @return string
     */
    public function getPid();

    /**
     * Get current job.
     *
     * @return JobInterface|null The job this worker is currently processing, if one at all.
     */
    public function getCurrentJob();

    /**
     * Get started at date time.
     *
     * @return DateTime|null The DateTime the worker was started.. if ever.
     */
    public function getStartedAtDateTime();
}
