<?php

namespace Resque\Component\Worker\Model;

use Resque\Component\Core\Process;
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
     * Add Queue
     *
     * @param QueueInterface $queue The queue to add to the worker.
     * @return $this
     */
    public function addQueue(QueueInterface $queue);

    /**
     * Get Queues
     *
     * @return QueueInterface[]
     */
    public function getQueues();

    /**
     * Work
     *
     * The worker should probably do some important stuff.
     */
    public function work();

    /**
     * @param Process $process
     * @return $this
     */
    public function setProcess(Process $process);

    /**
     * @return Process
     */
    public function getProcess();

    /**
     * @param JobInterface $job
     * @return $this
     */
    public function setCurrentJob(JobInterface $job);

    /**
     * @return JobInterface
     */
    public function getCurrentJob();
}
