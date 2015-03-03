<?php

namespace Resque\Component\Core;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Core\Redis\RedisClientAwareInterface;
use Resque\Component\Core\Redis\RedisClientInterface;
use Resque\Component\Queue\Event\QueueEvent;
use Resque\Component\Queue\Factory\QueueFactoryInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Queue\Registry\QueueRegistryInterface;
use Resque\Component\Queue\ResqueQueueEvents;

/**
 * Resque Redis queue registry
 */
class RedisQueueRegistry implements
    QueueRegistryInterface,
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
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(RedisClientInterface $redis, EventDispatcherInterface $eventDispatcher)
    {
        $this->setRedisClient($redis);
        $this->eventDispatcher = $eventDispatcher;
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
     * Get storage key for given queue
     *
     * @param QueueInterface $queue
     * @return string
     */
    protected function getRedisKey(QueueInterface $queue)
    {
        return 'queue:' . $queue->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function register(QueueInterface $queue)
    {
        $this->redis->sadd('queues', $queue->getName());

        $this->eventDispatcher->dispatch(ResqueQueueEvents::REGISTERED, new QueueEvent($queue));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isRegistered(QueueInterface $queue)
    {
        return $this->redis->exists($this->getRedisKey($queue));
    }

    /**
     * {@inheritDoc}
     */
    public function deregister(QueueInterface $queue)
    {
        $this->redis->multi();
        $this->redis->llen($this->getRedisKey($queue));
        $this->redis->del($this->getRedisKey($queue));
        $this->redis->srem('queues', $queue->getName());
        $responses = $this->redis->exec();

        if (isset($responses[0]) && $responses[0]) {
            $this->eventDispatcher->dispatch(ResqueQueueEvents::UNREGISTERED, new QueueEvent($queue));

            return $responses[0];
        }

        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function all()
    {
        $queuesNames = $this->redis->smembers('queues');
        $queues = array();

        foreach ($queuesNames as $queueName) {
            $queues[$queueName] = $this->createQueue($queueName);
        }

        return $queues;
    }

    /**
     * {@inheritDoc}
     */
    public function createQueue($name)
    {
        $queue = new RedisQueue($this->redis, $this->eventDispatcher);
        $queue->setName($name);

        return $queue;
    }
}
