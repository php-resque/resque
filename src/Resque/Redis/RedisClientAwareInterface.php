<?php

namespace Resque\Redis;

/**
 * Redis client aware
 */
interface RedisClientAwareInterface
{
    /**
     * Set redis client,
     *
     * @param RedisClientInterface $redis Client/connection to redis server/cluster.
     * @return void
     */
    public function setRedisClient(RedisClientInterface $redis);
}
