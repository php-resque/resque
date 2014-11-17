<?php

namespace Resque\Component\Core;

use Predis\ClientInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Queue\Registry\QueueRegistryInterface;

/**
 * Resque Redis queue registry
 */
class RedisQueueRegistry implements
    QueueRegistryInterface,
    RedisAwareInterface
{
    /**
     * @var ClientInterface
     */
    protected $redis;

    /**
     * Constructor
     *
     * @param ClientInterface $redis
     */
    public function __construct(ClientInterface $redis)
    {
        $this->setRedisClient($redis);
    }

    /**
     * {@inheritDoc}
     */
    public function setRedisClient(ClientInterface $redis)
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
        return isset($responses[0]) ? $responses[0] : 0;
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
        $queue = new RedisQueue($this->redis);
        $queue->setName($name);

        return $queue;
    }
}
