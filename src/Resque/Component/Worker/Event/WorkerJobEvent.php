<?php

namespace Resque\Component\Worker\Event;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Worker\Model\WorkerInterface;

class WorkerJobEvent extends WorkerEvent
{
    /**
     * @var JobInterface The job the worker just forked to perform for, of is about to perform for.
     */
    protected $job;

    public function __construct(WorkerInterface $worker, JobInterface $job)
    {
        parent::__construct($worker);

        $this->job = $job;
    }

    /**
     * @return JobInterface
     */
    public function getJob()
    {
        return $this->job;
    }
}
