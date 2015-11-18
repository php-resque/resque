<?php

namespace Resque\Component\Worker\Event;

use Resque\Component\Worker\WorkerDaemonInterface;

class WorkerDaemonSignalEvent extends WorkerDaemonEvent
{
    /**
     * @var string The signal the daemon received.
     */
    protected $signal;

    /**
     * Constructor.
     *
     * @param WorkerDaemonInterface $daemon
     * @param $signal
     */
    public function __construct(WorkerDaemonInterface $daemon, $signal)
    {
        $this->signal = $signal;

        parent::__construct($daemon);
    }
}
