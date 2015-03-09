<?php

namespace Resque\Redis;

use Resque\Component\Queue\Factory\QueueFactoryInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Queue\Registry\QueueRegistryInterface;

/**
 * Resque Redis queue registry
 */
class RedisQueueRegistry implements
    QueueRegistryInterface,
    RedisClientAwareInterface
{
    /**
     * @var RedisClientInterface
     */
    protected $redis;

    /**
     * @var QueueFactoryInterface
     */
    protected $queueFactory;

    /**
     * Constructor
     *
     * @param RedisClientInterface $redis
     * @param QueueFactoryInterface $queueFactory
     */
    public function __construct(RedisClientInterface $redis, QueueFactoryInterface $queueFactory)
    {
        $this->queueFactory = $queueFactory;
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

        if (isset($responses[0])) {
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
            $queues[$queueName] = $this->queueFactory->createQueue($queueName);
        }

        return $queues;
    }
}
