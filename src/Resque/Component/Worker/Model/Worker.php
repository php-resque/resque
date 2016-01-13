<?php

namespace Resque\Component\Worker\Model;

use Resque\Component\Queue\Model\QueueInterface;

class Worker implements WorkerInterface
{
    /**
     * @var array
     */
    protected $queues = [];

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->getHostname() . ':' . $this->getPid() . ':' . implode(',', $this->getAssignedQueues());
    }

    /**
     * {@inheritDoc}
     */
    public function getHostname()
    {
        // TODO: Implement getHostname() method.
    }

    /**
     * {@inheritDoc}
     */
    public function getAssignedQueues()
    {
        return $this->queues;
    }

    /**
     * {@inheritDoc}
     */
    public function getPid()
    {
        // TODO: Implement getPid() method.
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentJob()
    {
        // TODO: Implement getCurrentJob() method.
    }

    /**
     * {@inheritDoc}
     */
    public function getStartedAtDateTime()
    {
        // TODO: Implement getStartedAtDateTime() method.
    }

    /**
     * {@inheritDoc}
     */
    public function addQueue(QueueInterface $queue)
    {
        $this->queues[$queue->getName()] = $queue;
    }
}
