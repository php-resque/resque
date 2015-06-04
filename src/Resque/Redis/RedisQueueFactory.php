<?php

namespace Resque\Redis;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Queue\Factory\QueueFactoryInterface;
use Resque\Component\Queue\Model\Queue;

/**
 * Resque Redis queue registry
 */
class RedisQueueFactory implements
    QueueFactoryInterface,
    RedisClientAwareInterface
{
    /**
     * @var RedisClientInterface
     */
    protected $redis;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Constructor
     *
     * @param RedisClientInterface $redis
     * @todo remove $eventDispatcher if possible.
     */
    public function __construct(RedisClientInterface $redis, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->setRedisClient($redis);
    }

    /**
     * {@inheritDoc}
     */
    public function setRedisClient(RedisClientInterface $redis)
    {
        $this->redis = $redis;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function createQueue($name)
    {
        $queue = new Queue(
            $name,
            new RedisQueueStorage($this->redis),
            $this->eventDispatcher
        );

        return $queue;
    }
}
