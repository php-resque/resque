<?php

namespace Resque\Event;

use Resque\WorkerInterface;
use Resque\Job\JobInterface;

/**
 * AbstractJobPerformEvent
 *
 * DRY for WorkerBeforeForkEvent and WorkerAfterForkEvent.
 *
 * @see WorkerBeforeForkEvent
 * @see WorkerAfterForkEvent
 */
abstract class AbstractWorkerForkEvent
{
    /**
     * @var WorkerInterface The worker that dispatched this event.
     */
    protected $worker;

    /**
     * @var JobInterface The job the worker just forked to perform for, of is about to perform for.
     */
    protected $job;

    public function __construct(WorkerInterface $worker, JobInterface $job)
    {
        $this->worker = $worker;
        $this->job = $job;
    }

    /**
     * @return WorkerInterface
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * @return JobInterface
     */
    public function getJob()
    {
        return $this->job;
    }
}
