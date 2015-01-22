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
     * Add queue
     *
     * @param QueueInterface $queue The queue to add to the worker.
     * @return $this
     */
    public function addQueue(QueueInterface $queue);

    /**
     * Get queues
     *
     * Return an array containing all of the queues that this worker is using when searching for jobs.
     *
     * @return QueueInterface[] Array of queues this worker is dealing with.
     */
    public function getQueues();

    /**
     * Work
     *
     * The worker should probably do some important stuff... like process jobs.
     */
    public function work();

    /**
     * Set process
     *
     * @param Process $process
     * @return $this
     */
    public function setProcess(Process $process);

    /**
     * Get process
     *
     * @return Process
     */
    public function getProcess();

    /**
     * Set current job
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

    /**
     * Stop
     *
     * The worker will finish up the current job, if there is one at all, and then exit.
     *
     * @return $this
     */
    public function stop();

    /**
     * Halt
     *
     * The worker will immediately halt any current job and then exit.
     *
     * @return $this
     */
    public function halt();

    /**
     * Pause
     *
     * The worker will finish up the current job, if there is one at all,and then it will
     * wait and not request any new jobs.
     *
     * @return $this
     */
    public function pause();

    /**
     * Resume
     *
     * This allows the worker to begin processing jobs again, assuming the worker was paused.
     *
     * @return $this
     */
    public function resume();

    /**
     * Halt current job
     *
     * If the worker is currently processing a job, it should immediately halt it causing it
     * to fail, and then continue normal operation.
     *
     * @return $this
     */
    public function haltCurrentJob();
}
