<?php

namespace Resque\Component\Worker\Model;

class Worker implements WorkerInterface
{
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
        // TODO: Implement getAssignedQueues() method.
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
}
