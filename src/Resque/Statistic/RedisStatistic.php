<?php

namespace Resque\Statistic;

use Predis\ClientInterface;

/**
 * Default redis backend for storing failed jobs.
 */
class RedisStatistic implements StatisticInterface
{
    /**
     * @var ClientInterface A redis client.
     */
    protected $redis;

    public function __construct(ClientInterface $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Get the value of the supplied statistic counter for the specified statistic.
     *
     * @param string $stat The name of the statistic to get the stats for.
     * @return mixed Value of the statistic.
     */
    public function get($stat)
    {
        return (int)$this->redis->get('stat:' . $stat);
    }

    /**
     * Increment the value of the specified statistic by a certain amount (default is 1)
     *
     * @param string $stat The name of the statistic to increment.
     * @param int $by The amount to increment the statistic by.
     * @return boolean True if successful, false if not.
     */
    public function increment($stat, $by = 1)
    {
        return (bool)$this->redis->incrby('stat:' . $stat, $by);
    }

    /**
     * Decrement the value of the specified statistic by a certain amount (default is 1)
     *
     * @param string $stat The name of the statistic to decrement.
     * @param int $by The amount to decrement the statistic by.
     * @return boolean True if successful, false if not.
     */
    public function decrement($stat, $by = 1)
    {
        return (bool)$this->redis->decrby('stat:' . $stat, $by);
    }

    /**
     * Delete a statistic with the given name.
     *
     * @param string $stat The name of the statistic to delete.
     * @return boolean True if successful, false if not.
     */
    public function clear($stat)
    {
        return (bool)$this->redis->del('stat:' . $stat);
    }
}
