<?php

namespace Resque\Component\Worker\Event;

use Resque\Component\Worker\WorkerProcessInterface;

class WorkerProcessSignalEvent extends WorkerProcessEvent
{
    /**
     * @var string The signal the worker received.
     */
    protected $signal;

    /**
     * Constructor.
     *
     * @param WorkerProcessInterface $workerProcess
     * @param string $signal The signal the worker received.
     */
    public function __construct(WorkerProcessInterface $workerProcess, $signal)
    {
        $this->signal = $signal;

        parent::__construct($workerProcess);
    }
}
