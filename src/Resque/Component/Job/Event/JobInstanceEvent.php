<?php

namespace Resque\Component\Job\Event;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\PerformantJobInterface;

/**
 * JobInstanceEvent
 */
class JobInstanceEvent
{
    /**
     * @var JobInterface The job that just performed, or is about to.
     */
    protected $job;

    /**
     * @var PerformantJobInterface The instance of $job->getClassName() that has just performed, or is about to.
     */
    protected $instance;

    public function __construct(JobInterface $job, PerformantJobInterface $instance)
    {
        $this->job = $job;
        $this->instance = $instance;
    }

    /**
     * @return PerformantJobInterface
     */
    public function getJobInstance()
    {
        return $this->instance;
    }

    /**
     * @return JobInterface
     */
    public function getJob()
    {
        return $this->job;
    }
}
