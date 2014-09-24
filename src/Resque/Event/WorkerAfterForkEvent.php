<?php

namespace Resque\Event;

class WorkerAfterForkEvent implements EventInterface
{
    public function getName()
    {
        return 'resque.worker.after_fork';
    }
}
