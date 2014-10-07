<?php

namespace Resque\Event;

class WorkerAfterForkEvent extends AbstractWorkerForkEvent implements EventInterface
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'resque.worker.after_fork';
    }
}
