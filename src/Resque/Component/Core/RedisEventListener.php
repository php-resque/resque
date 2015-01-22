<?php

namespace Resque\Component\Core;

use Predis\ClientInterface;
use Resque\Component\Core\Redis\RedisClientAwareInterface;
use Resque\Component\Core\Redis\RedisClientInterface;
use Resque\Component\Worker\Event\WorkerJobEvent;

class RedisEventListener implements RedisClientAwareInterface
{
    /**
     * @var ClientInterface
     */
    protected $redis;

    public function __construct(RedisClientInterface $redis)
    {
        $this->setRedisClient($redis);
    }

    /**
     * @param ClientInterface|RedisClientInterface $redis
     * @return $this
     */
    public function setRedisClient(RedisClientInterface $redis)
    {
        $this->redis = $redis;

        return $this;
    }

    /**
     * @param WorkerJobEvent $event
     */
    public function disconnectFromRedis(WorkerJobEvent $event)
    {
        $this->redis->disconnect();
    }
}
