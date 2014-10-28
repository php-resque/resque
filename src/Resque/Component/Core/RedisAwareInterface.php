<?php

namespace Resque\Component\Core;

use Predis\ClientInterface;

/**
 * Redis aware
 */
interface RedisAwareInterface
{
    /**
     * @param ClientInterface $redis
     * @return self
     */
    public function setRedisClient(ClientInterface $redis);
}
