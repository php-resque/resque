<?php

namespace Resque\Redis;

use Predis\ClientInterface;
use Resque\Component\Job\Event\JobFailedEvent;
use Resque\Component\Statistic\StatisticInterface;
use Resque\Component\Worker\Event\WorkerJobEvent;

/**
 * Default redis backend for storing failed jobs.
 */
class RedisStatistic implements
    StatisticInterface,
    RedisClientAwareInterface
{
    /**
     * @var ClientInterface A redis client.
     */
    protected $redis;

    /**
     * Constructor.
     *
     * @param RedisClientInterface $redis A Redis client.
     */
    public function __construct(RedisClientInterface $redis)
    {
        $this->setRedisClient($redis);
    }

    /**
     * {@inheritDoc}
     */
    public function setRedisClient(RedisClientInterface $redis)
    {
        $this->redis = $redis;

        return $this;
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

    /**
     * @param WorkerJobEvent $event
     */
    public function jobProcessed(WorkerJobEvent $event)
    {
        $this->increment('processed');
        $this->increment('processed:' . $event->getWorker()->getId());
    }

    /**
     * @param JobFailedEvent $event
     */
    public function jobFailed(JobFailedEvent $event)
    {
        $this->increment('failed');
        $this->increment('failed:' . $event->getWorker()->getId());
    }
}
