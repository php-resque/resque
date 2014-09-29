<?php

namespace Resque\Event;

use Resque\WorkerInterface;

class WorkerStartupEvent implements EventInterface
{
    /**
     * @var WorkerInterface The worker that dispatched this event.
     */
    protected $worker;

    public function __construct(WorkerInterface $worker)
    {
        $this->worker = $worker;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'resque.worker.start_up';
    }

    /**
     * @return WorkerInterface
     */
    public function getWorker()
    {
        return $this->worker;
    }
}
