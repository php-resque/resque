<?php

namespace Resque\Redis\Bridge;

use Predis\ClientInterface;
use Resque\Redis\RedisClientInterface;

/**
 * Predis bridge.
 *
 * Predis 1.x is a hard to spec against as it relies on magic functions, and provides
 * no interfaces or concrete implementation with it's supported methods defined. To combat
 * this RedisClientInterface was created and this is just the boring bridge to Predis.
 */
class PredisBridge implements RedisClientInterface
{
    /**
     * @var ClientInterface
     */
    protected $predis;

    /**
     * Constructor.
     *
     * @param ClientInterface $predis A Predis client.
     */
    public function __construct(ClientInterface $predis)
    {
        $this->predis = $predis;
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect()
    {
        return $this->predis->disconnect();
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value)
    {
        return $this->predis->set($key, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function get($key)
    {
        return $this->predis->get($key);
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        return $this->predis->exists($key);
    }

    /**
     * {@inheritDoc}
     */
    public function del($key)
    {
        return $this->predis->del($key);
    }

    /**
     * {@inheritDoc}
     */
    public function smembers($key)
    {
        return $this->predis->smembers($key);
    }

    /**
     * {@inheritDoc}
     */
    public function sismember($key, $member)
    {
        return $this->predis->sismember($key, $member);
    }

    /**
     * {@inheritDoc}
     */
    public function scard($key)
    {
        return $this->predis->scard($key);
    }

    /**
     * {@inheritDoc}
     */
    public function sadd($key, $member)
    {
        return $this->predis->sadd($key, $member);
    }

    /**
     * {@inheritDoc}
     */
    public function srem($key, $member)
    {
        return $this->predis->srem($key, $member);
    }

    /**
     * {@inheritDoc}
     */
    public function llen($key)
    {
        return $this->predis->llen($key);
    }

    /**
     * {@inheritDoc}
     */
    public function lindex($key, $index)
    {
        return $this->predis->lindex($key, $index);
    }

    /**
     * {@inheritDoc}
     */
    public function lpop($key)
    {
        return $this->predis->lpop($key);
    }

    /**
     * {@inheritDoc}
     */
    public function rpush($key, $value)
    {
        return $this->predis->rpush($key, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function rpop($key)
    {
        return $this->predis->rpop($key);
    }

    /**
     * {@inheritDoc}
     */
    public function rpoplpush($source, $destination)
    {
        return $this->predis->rpoplpush($source, $destination);
    }

    /**
     * {@inheritDoc}
     */
    public function flushdb()
    {
        return $this->predis->flushdb();
    }

    /**
     * {@inheritDoc}
     */
    public function incr($key)
    {
        return $this->predis->incr($key);
    }

    /**
     * {@inheritDoc}
     */
    public function incrby($key, $increment)
    {
        return $this->predis->incrby($key, $increment);
    }

    /**
     * {@inheritDoc}
     */
    public function decr($key)
    {
        return $this->predis->decr($key);
    }

    /**
     * {@inheritDoc}
     */
    public function decrby($key, $decrement)
    {
        return $this->predis->decrby($key, $decrement);
    }

    /**
     * {@inheritDoc}
     */
    public function discard()
    {
        return $this->predis->discard();
    }

    /**
     * {@inheritDoc}
     */
    public function exec()
    {
        return $this->predis->exec();
    }

    /**
     * {@inheritDoc}
     */
    public function multi()
    {
        return $this->predis->multi();
    }

    /**
     * {@inheritDoc}
     */
    public function expire($key, $ttl)
    {
        return $this->predis->expire($key, $ttl);
    }
}
