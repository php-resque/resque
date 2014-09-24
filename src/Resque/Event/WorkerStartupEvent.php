<?php

namespace Resque\Event;

use Resque\WorkerInterface;

class WorkerStartupEvent implements EventInterface
{
    public function __construct(WorkerInterface $worker)
    {

    }

    public function getName()
    {
        return 'resque.worker.start_up';
    }
}
