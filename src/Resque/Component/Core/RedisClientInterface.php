<?php

namespace Resque\Component\Core;

/**
 * Redis client interface
 *
 * This just makes it easier for me to test/spec, as Predis does not have a rigid client interface. Though it does
 * allow anyone to bridge in their own redis client/connection.
 */
interface RedisClientInterface
{
    public function disconnect();

    public function set($key, $value);
    public function del($key);

    public function smembers($key);
    public function scard($key);
}
