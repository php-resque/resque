<?php

namespace Resque\Failure;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Worker\Model\WorkerInterface;

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
