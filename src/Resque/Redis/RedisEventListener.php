<?php

namespace Resque\Redis;

use Predis\ClientInterface;
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

    public function disconnectFromRedis()
    {
        $this->redis->disconnect();
    }
}
