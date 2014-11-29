<?php

namespace Resque\Component\Job\Failure;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Worker\Model\WorkerInterface;

/**
 * Resque failure backend interface
 *
 * Defines how failed jobs are kept and managed.
 */
interface FailureInterface
{
    /**
     * Record job failure
     *
     * @param JobInterface $job The job that just failed to perform cleanly.
     * @param \Exception $exception The exception for the cause of the failure.
     * @param WorkerInterface $worker The worker that the job failed to perform with in.
     *
     * @return void
     */
    public function save(JobInterface $job, \Exception $exception, WorkerInterface $worker);

    /**
     * Number of failed jobs
     *
     * @return int
     */
    public function count();

    /**
     * Clear all saved failures
     *
     * @return void
     */
    public function clear();
}
