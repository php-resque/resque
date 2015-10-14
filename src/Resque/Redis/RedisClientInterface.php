<?php

namespace Resque\Redis;

/**
 * Redis client
 *
 * This just makes it easier to test/spec, as Predis does not have a rigid client interface. It's also
 * easy enough now to see the Redis commands used thus far in the project.
 *
 * This does also allow anyone to bridge in their own Redis client/connection.
 */
interface RedisClientInterface
{
    public function disconnect();

    public function set($key, $value);
    public function get($key);
    public function exists($key);
    public function expire($key, $ttl);

    /**
     * @return int
     */
    public function del($key);

    /**
     * @return int
    */
    public function incr($key);

    /**
     * @return int
     */
    public function incrby($key, $increment);

    /**
     * @return int
     */
    public function decr($key);

    /**
     * @return int
     */
    public function decrby($key, $decrement);

    /**
     * @return string[]
     */
    public function smembers($key);
    public function sismember($key, $member);
    public function scard($key);
    public function sadd($key, $member);
    public function srem($key, $member);

    /**
     * @return int
     */
    public function llen($key);

    /**
     * @return int
     */
    public function lindex($key, $index);

    /**
     * @return string
     */
    public function lpop($key);

    /**
     * @return int
     */
    public function rpush($key, $value);

    /**
     * @return string
     */
    public function rpop($key);

    /**
     * @return string
     */
    public function rpoplpush($source, $destination);

    public function discard();
    public function exec();
    public function multi();

    /**
     * @return mixed
     */
    public function flushdb();
}
