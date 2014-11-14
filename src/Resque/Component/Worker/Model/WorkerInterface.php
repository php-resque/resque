<?php

namespace Resque\Component\Worker\Model;

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
}
