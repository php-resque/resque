<?php

namespace Resque\Failure;

use Resque\Job\JobInterface;
use Resque\WorkerInterface;
use Resque\QueueInterface;

/**
 * Null job failure handler
 *
 * Does nothing.
 */
class Null implements FailureInterface
{
    public function save(JobInterface $job, \Exception $exception, WorkerInterface $worker)
    {
        return;
    }

    public function count()
    {
        return 0;
    }

    public function clear()
    {
        return true;
    }
}
