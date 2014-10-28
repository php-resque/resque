<?php

namespace Resque\Event;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Worker\Model\WorkerInterface;

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
     * @var \Resque\Component\Worker\Model\WorkerInterface The worker that dispatched this event.
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
     * @return \Resque\Component\Worker\Model\WorkerInterface
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
