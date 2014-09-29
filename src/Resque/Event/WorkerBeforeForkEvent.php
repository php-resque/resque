<?php

namespace Resque\Event;

use Resque\WorkerInterface;
use Resque\Job\JobInterface;

class WorkerBeforeForkEvent implements EventInterface
{
    /**
     * @var WorkerInterface The worker that dispatched this event.
     */
    protected $worker;

    /**
     * @var JobInterface The job the worker is forking to perform.
     */
    protected $job;

    public function __construct(WorkerInterface $worker, JobInterface $job)
    {
        $this->worker = $worker;
        $this->job = $job;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'resque.worker.before_fork';
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
