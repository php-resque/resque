<?php

namespace Resque\Component\Queue;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Model\AbstractQueue;
use Resque\Component\Queue\Model\Queue;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Queue\Registry\QueueRegistryInterface;

/**
 * Resque wildcard queue.
 *
 * Provides the ability to pull jobs from all known queues. Optionally allows for a prefix, so foo* like filtering
 * can be used, useful if you use a single redis instance that multiple projects talk too and you give projects queue
 * prefixes.
 */
class WildcardQueue extends Queue
{
    /**
     * @var QueueRegistryInterface A queue registry.
     */
    protected $registry;

    /**
     * @var null|string Prefix to wildcard, eg "acme-" makes the wildcard behave like "acme-*"
     */
    protected $prefix;

    /**
     * @param string|null $prefix
     * @param QueueRegistryInterface $registry
     */
    public function __construct(QueueRegistryInterface $registry, $prefix = null)
    {
        $this->name = $prefix . '*';
        $this->prefix = $prefix;
        $this->registry = $registry;
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
    public function enqueue(JobInterface $job)
    {
        throw new \Exception('Wildcard queue does not support pushing');
    }

    /**
     * {@inheritdoc}
     *
     * Queues will be searched in alphabetic order.
     */
    public function dequeue()
    {
        $queues = $this->registry->all();

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
            if (null !== $job = $queue->dequeue()) {
                return $job;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        throw new \Exception('Wildcard queue does not support counting');
    }
}
