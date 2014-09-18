<?php

namespace Resque\Failure;

use Resque\Job;
use Resque\WorkerInterface;
use Resque\QueueInterface;

/**
 * @todo name better
 */
interface FailureInterface
{
    /**
     * Record failure
     *
     * @param Job $job
     * @param \Exception $exception
     * @param QueueInterface $queue
     * @param WorkerInterface $worker
     * @return mixed
     */
    public function save(Job $job, \Exception $exception, QueueInterface $queue, WorkerInterface $worker);
}
