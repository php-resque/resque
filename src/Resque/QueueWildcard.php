<?php

namespace Resque;

/**
 * Resque Queue
 *
 * Provides the ability to push and pull jobs from a queue, along side other utility functions.
 */
class QueueWildcard implements QueueInterface
{
    /**
     * {@inheritdoc}
     */
    public function push(Job $job)
    {
        throw new \Exception('Wild card Queue does not support pushing.');
    }

    /**
     * {@inheritdoc}
     */
    public function pop()
    {
       return null;
    }

    public function __toString()
    {
        return '*';
    }
}
