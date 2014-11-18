<?php

namespace Resque\Component\Worker\Model;

use Resque\Component\Core\Process;
use Resque\Component\Queue\Model\QueueInterface;

interface WorkerInterface
{
    /**
     * Get Id
     *
     * @return string The id of this worker.
     */
    public function getId();

    /**
     * Set hostname
     *
     * @param string $hostname The name of the host the worker is/was running on.
     * @return $this
     */
    public function setHostname($hostname);

    /**
     * Get hostname
     *
     * @return string $hostname The name of the host the worker is/was running on.
     */
    public function getHostname();

    /**
     * Add Queue
     *
     * @param QueueInterface $queue The queue to add to the worker.
     */
    public function addQueue(QueueInterface $queue);

    /**
     * Work
     *
     * The worker should probably do some important stuff.
     */
    public function work();

    public function setProcess(Process $process);
}
