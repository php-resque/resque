<?php

namespace Resque\Component\Core\Redis;

/**
 * Redis client interface
 *
 * This just makes it easier for me to test/spec, as Predis does not have a rigid client interface. It's also
 * easy enough now to see the redis commands used thus far in the project.
 *
 * This does also allow anyone to to bridge in their own redis client/connection.
 */
interface RedisClientInterface
{
    public function disconnect();

    public function set($key, $value);
    public function get($key);
    public function exists($key);

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
