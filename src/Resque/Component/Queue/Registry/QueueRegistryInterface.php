<?php

namespace Resque\Component\Queue\Registry;

use Resque\Component\Queue\Model\QueueInterface;

interface QueueRegistryInterface
{
    /**
     * Registers the given queue in storage
     *
     * @param QueueInterface $queue
     *
     * @return $this
     */
    public function register(QueueInterface $queue);

    /**
     * Is the given queue already registered?
     *
     * @param QueueInterface $queue
     *
     * @return bool
     */
    public function isRegistered(QueueInterface $queue);

    /**
     * Removes the given queue and it's jobs with in it
     *
     * @param QueueInterface $queue
     *
     * @return integer The number of jobs removed
     */
    public function deregister(QueueInterface $queue);

    /**
     * Return array of all registered queues.
     *
     * @return QueueInterface[]
     */
    public function all();
}
