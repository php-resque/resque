<?php

namespace Resque\Component\Job\Event;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\PerformantJobInterface;
use Resque\Component\Worker\Event\WorkerJobEvent;
use Resque\Component\Worker\Model\WorkerInterface;

/**
 * JobInstanceEvent
 */
class JobInstanceEvent extends WorkerJobEvent
{
    /**
     * @var PerformantJobInterface The instance of $job->getClassName() that has just performed, or is about to.
     */
    protected $instance;

    public function __construct(WorkerInterface $worker, JobInterface $job, PerformantJobInterface $instance)
    {
        $this->instance = $instance;
        parent::__construct($worker,$job);
    }

    /**
     * @return PerformantJobInterface
     */
    public function getJobInstance()
    {
        return $this->instance;
    }
}
