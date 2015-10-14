<?php

namespace Resque\Redis;

use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Queue\Registry\QueueRegistryAdapterInterface;

/**
 * Redis queue registry adapter
 *
 * Connects Redis in to the Resque core, and stores queues the original Resque way.
 */
class RedisQueueRegistryAdapter implements
    QueueRegistryAdapterInterface,
    RedisClientAwareInterface
{
    /**
     * @var RedisClientInterface
     */
    protected $redis;

    /**
     * Constructor.
     *
     * @param RedisClientInterface $redis
     */
    public function __construct(RedisClientInterface $redis)
    {
        $this->setRedisClient($redis);
    }

    /**
     * {@inheritDoc}
     */
    public function setRedisClient(RedisClientInterface $redis)
    {
        $this->redis = $redis;
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
    public function save(QueueInterface $queue)
    {
        $this->redis->sadd('queues', $queue->getName());

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function has(QueueInterface $queue)
    {
        return $this->redis->exists($this->getRedisKey($queue)) == 1 ? true : false;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(QueueInterface $queue)
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
        return $this->redis->smembers('queues');
    }
}
