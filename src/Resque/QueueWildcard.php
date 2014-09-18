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
        throw new \Exception('Wildcard queue does not support pushing');
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        throw new \Exception('Wildcard queue can not be registered');
    }

    /**
     * {@inheritdoc}
     *
     * Queues will be searched in alphabetic order.
     */
    public function pop()
    {
        $queues = $this->all();

        ksort($queues);

        foreach ($queues as $queue) {
            $queue->setRedisBackend($this->redis);

            if (null !== $job = $queue->pop()) {

                return $job;
            }
        }

        return null;
    }
}
