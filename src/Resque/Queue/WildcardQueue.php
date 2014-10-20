<?php

namespace Resque\Queue;

use Resque\Job\JobInterface;
use Resque\Queue;

/**
 * Resque wildcard queue
 *
 * Provides the ability to pull jobs from all known queues. Optionally allows for a prefix, so foo* like filtering
 * can be used, useful if you use a single redis instance that multiple projects talk too and you give projects queue
 * prefixes.
 */
class WildcardQueue extends Queue
{
    /**
     * @var null
     */
    protected $prefix;

    /**
     * @param string|null $prefix
     */
    public function __construct($prefix = null)
    {
        $this->name = $prefix . '*';
        $this->prefix = $prefix;
    }

    /**
     * @return null|string The prefix assigned to this wildcard queue
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function push(JobInterface $job)
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

        if (null !== $this->prefix) {
            $wildcardQueue = $this;
            $queues = array_filter(
                $queues,
                function (QueueInterface $queue) use ($wildcardQueue) {
                    return (0 === strpos($queue->getName(), $wildcardQueue->getPrefix()));
                }
            );
        }

        ksort($queues);

        foreach ($queues as $queue) {
            $queue->setRedisBackend($this->redis); // @todo should I be doing this, or should static::all() ?

            if (null !== $job = $queue->pop()) {

                return $job;
            }
        }

        return null;
    }
}
