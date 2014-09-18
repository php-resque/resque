<?php

namespace Resque\Failure;

use Resque\Job;
use Resque\WorkerInterface;
use Resque\QueueInterface;

/**
 * Null job fail handler
 *
 * Does nothing.
 */
class Null implements FailureInterface
{
    public function save(Job $job, \Exception $exception, QueueInterface $queue, WorkerInterface $worker)
    {
        return;
    }
}
