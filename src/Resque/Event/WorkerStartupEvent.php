<?php

namespace Resque\Event;

class WorkerStartupEvent implements EventInterface
{
    public function getName()
    {
        return 'resque.worker.start_up';
    }
}
