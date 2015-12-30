<?php

namespace Resque\Component\Worker\Event;

use Resque\Component\Worker\WorkerProcessInterface;

class WorkerProcessEvent
{
    /**
     * @var WorkerProcessInterface The worker that dispatched this event.
     */
    protected $process;

    /**
     * Constructor.
     *
     * @param WorkerProcessInterface $process
     */
    public function __construct(WorkerProcessInterface $process)
    {
        $this->process = $process;
    }

    /**
     * @return WorkerProcessInterface
     */
    public function getWorkerProcess()
    {
        return $this->process;
    }
}
