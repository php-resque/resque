<?php

namespace Resque\Event;

class WorkerBeforeForkEvent extends AbstractWorkerForkEvent implements EventInterface
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'resque.worker.before_fork';
    }
}
