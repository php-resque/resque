<?php

namespace Resque\Failure;

use Resque\Job\JobInterface;
use Resque\WorkerInterface;

/**
 * Black hole job failure handler
 *
 * Does nothing.
 */
class BlackHoleFailure implements FailureInterface
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
