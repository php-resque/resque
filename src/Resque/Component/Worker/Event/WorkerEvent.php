<?php

namespace Resque\Component\Worker\Event;

use Resque\Component\Worker\Model\WorkerInterface;

class WorkerEvent
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
     * @return WorkerInterface
     */
    public function getWorker()
    {
        return $this->worker;
    }
}
