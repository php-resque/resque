<?php

namespace Resque;

/**
 * Resque Wildcard Queue
 *
 * Provides the ability to pull jobs from all known queues.
 */
class QueueWildcard extends Queue
{
    public function __construct()
    {
        $this->name = '*';
    }

    /**
     * {@inheritdoc}
     */
    public function push(Job $job)
    {
        throw new \Exception('Wild card queue does not support pushing');
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        throw new \Exception('Wild card queue can not be registered');
    }

    /**
     * {@inheritdoc}
     */
    public function pop()
    {
        foreach ($this->all() as $queue) {

            $queue->setRedisBackend($this->redis);

            if (null !== $job = $queue->pop()) {

                return $job;
            }
        }

        return null;
    }
}
