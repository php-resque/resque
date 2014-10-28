<?php

namespace Resque\Event;

use Resque\Component\Worker\Model\WorkerInterface;

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
     * @return \Resque\Component\Worker\Model\WorkerInterface
     */
    public function getWorker()
    {
        return $this->worker;
    }
}
