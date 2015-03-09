<?php

namespace Resque\Redis;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\Model\JobTrackerInterface;
use Resque\Component\Job\Model\TrackableJobInterface;

/**
 * Status tracker/information for a job.
 */
class RedisJobTracker implements
    JobTrackerInterface,
    RedisClientAwareInterface
{
    public static $completedStates = array(
        JobInterface::STATE_FAILED,
        JobInterface::STATE_COMPLETE,
    );

    /**
     * @var RedisClientInterface
     */
    protected $redis;

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
    }

    /**
     * Get redis key
     *
     * @param JobInterface $job
     * @return string
     */
    protected function getRedisKey(JobInterface $job)
    {
        return 'job:' . $job->getId() . ':status';
    }

    /**
     * {@inheritDoc}
     */
    public function isTracking(JobInterface $job)
    {
        return $this->redis->exists($this->getRedisKey($job));
    }

    /**
     * {@inheritDoc}
     */
    public function track(TrackableJobInterface $job)
    {
        $statusPacket = array(
            'status' => $job->getState(),
            'updated' => date('c'),
        );

        $this->redis->set($this->getRedisKey($job), json_encode($statusPacket));

        // Expire the status for completed jobs after 24 hours
        if (in_array($job->getState(), static::$completedStates)) {
            $this->redis->expire($this->getRedisKey($job), 86400);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(JobInterface $job)
    {
        if (!$this->isTracking($job)) {
            return false;
        }

        $statusPacket = json_decode($this->redis->get($this->getRedisKey($job)), true);

        if (!$statusPacket) {
            return false;
        }

        return $statusPacket['status'];
    }

    /**
     * {@inheritDoc}
     */
    public function stop(JobInterface $job)
    {
        $this->redis->del($this->getRedisKey($job));
    }

    /**
     * {@inheritDoc}
     */
    public function isComplete(JobInterface $job)
    {
        $state = $this->get($job);

        if (in_array($state, static::$completedStates)) {
            return true;
        }

        return false;
    }
}
