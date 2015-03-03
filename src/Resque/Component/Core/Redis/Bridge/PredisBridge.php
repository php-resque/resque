<?php

namespace Resque\Component\Core\Redis\Bridge;

use Predis\ClientInterface;
use Resque\Component\Core\Redis\RedisClientInterface;

class PredisBridge implements RedisClientInterface
{
    /**
     * @var ClientInterface
     */
    protected $predis;

    public function __construct(ClientInterface $predis)
    {
        $this->predis = $predis;
    }

    public function disconnect()
    {
        return $this->predis->disconnect();
    }

    public function set($key, $value)
    {
        return $this->predis->set($key, $value);
    }

    public function get($key)
    {
        return $this->predis->get($key);
    }

    public function exists($key)
    {
        return $this->predis->exists($key);
    }

    /**
     * @return int
     */
    public function del($key)
    {
        return $this->predis->del($key);
    }

    /**
     * @return string[]
     */
    public function smembers($key)
    {
        return $this->predis->smembers($key);
    }

    public function sismember($key, $member)
    {
        return $this->predis->sismember($key, $member);
    }

    public function scard($key)
    {
        return $this->predis->scard($key);
    }

    public function sadd($key, $member)
    {
        return $this->predis->sadd($key, $member);
    }

    public function srem($key, $member)
    {
        return $this->predis->srem($key, $member);
    }

    /**
     * @return int
     */
    public function llen($key)
    {
        return $this->predis->llen($key);
    }

    public function lindex($key, $index)
    {
        return $this->predis->lindex($key, $index);
    }

    /**
     * @return string
     */
    public function lpop($key)
    {
        return $this->predis->lpop($key);
    }

    /**
     * @return int
     */
    public function rpush($key, $value)
    {
        return $this->predis->rpush($key, $value);
    }

    /**
     * @return string
     */
    public function rpop($key)
    {
        return $this->predis->rpop($key);
    }

    /**
     * @return string
     */
    public function rpoplpush($source, $destination)
    {
        return $this->predis->rpoplpush($source, $destination);
    }

    /**
     * @return mixed
     */
    public function flushdb()
    {
        return $this->predis->flushdb();
    }

    /**
     * @return int
     */
    public function incr($key)
    {
        return $this->predis->incr($key);
    }

    /**
     * @return int
     */
    public function incrby($key, $increment)
    {
        return $this->predis->incrby($key, $increment);
    }

    /**
     * @return int
     */
    public function decr($key)
    {
        return $this->predis->decr($key);
    }

    /**
     * @return int
     */
    public function decrby($key, $decrement)
    {
        return $this->predis->decrby($key, $decrement);
    }

    public function discard()
    {
        return $this->predis->discard();
    }

    public function exec()
    {
        return $this->predis->exec();
    }

    public function multi()
    {
        return $this->predis->multi();
    }

    public function expire($key, $ttl)
    {
        return $this->predis->expire($key, $ttl);
    }
}
