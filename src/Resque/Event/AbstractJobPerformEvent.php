<?php

namespace Resque\Event;

use Resque\Job\JobInterface;
use Resque\Job\PerformantJobInterface;

/**
 * AbstractJobPerformEvent
 *
 * DRY for JobBeforePerformEvent and JobAfterPerformEvent.
 *
 * @see JobBeforePerformEvent
 * @see JobAfterPerformEvent
 */
abstract class AbstractJobPerformEvent
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
    public function getInstance()
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
