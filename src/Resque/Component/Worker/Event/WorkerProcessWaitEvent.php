<?php

namespace Resque\Component\Worker\Event;

class WorkerProcessWaitEvent extends WorkerProcessEvent
{
    /**
     * @var int The number of milliseconds to wait.
     */
    protected $waitTime = 2000000;

    /**
     * Get ms to wait.
     *
     * @return int The number of milliseconds to wait.
     */
    public function getMsToWait()
    {
        return $this->waitTime;
    }

    /**
     * Set ms to wait.
     *
     * @param int $msToWait The number of milliseconds the worker process should wait.
     */
    public function setMsToWait($msToWait)
    {
        $this->waitTime = $msToWait;
    }
}
