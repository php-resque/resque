<?php

namespace Resque\Failure;

use Resque\Job\JobInterface;
use Resque\WorkerInterface;
use Resque\QueueInterface;

/**
 * @todo name better
 */
interface FailureInterface
{
    /**
     * Record job failure
     *
     * @param JobInterface $job
     * @param \Exception $exception
     * @param QueueInterface $queue
     * @param WorkerInterface $worker
     * @return mixed
     */
    public function save(JobInterface $job, \Exception $exception, QueueInterface $queue, WorkerInterface $worker);

    /**
     * Number of failures
     *
     * @return mixed
     */
    public function count();

    /**
     * Clear all saved failures
     *
     * @return mixed
     */
    public function clear();
}
