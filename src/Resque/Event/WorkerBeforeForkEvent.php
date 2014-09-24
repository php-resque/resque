<?php

namespace Resque\Event;

use Resque\WorkerInterface;
use Resque\Job;

class WorkerBeforeForkEvent implements EventInterface
{
    public function __construct(WorkerInterface $worker, Job $job)
    {

    }

    public function getName()
    {
        return 'resque.worker.before_fork';
    }
}
