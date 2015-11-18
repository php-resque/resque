<?php

namespace Resque\Component\Worker\Event;

use Resque\Component\Worker\WorkerDaemonInterface;

class WorkerDaemonEvent
{
    /**
     * @var WorkerDaemonInterface The worker that dispatched this event.
     */
    protected $daemon;

    /**
     * Constructor.
     *
     * @param WorkerDaemonInterface $daemon
     */
    public function __construct(WorkerDaemonInterface $daemon)
    {
        $this->daemon = $daemon;
    }

    /**
     * @return WorkerDaemonInterface
     */
    public function getWorkerDaemon()
    {
        return $this->daemon;
    }
}
