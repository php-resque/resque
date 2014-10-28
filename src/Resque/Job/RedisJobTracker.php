<?php

namespace Resque\Job;

use Predis\ClientInterface;
use Resque\Component\Core\RedisAwareInterface;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\Model\JobTrackerInterface;
use Resque\Component\Job\Model\TrackableJobInterface;

/**
 * Status tracker/information for a job.
 */
class RedisJobTracker implements
    JobTrackerInterface,
    RedisAwareInterface
{
    public static $completedStates = array(
        JobInterface::STATE_FAILED,
        JobInterface::STATE_COMPLETE,
    );

    /**
     * @var ClientInterface
     */
    protected $redis;

    public function __construct(ClientInterface $redis)
    {
        $this->setRedisClient($redis);
    }

    public function setRedisClient(ClientInterface $redis)
    {
        $this->redis = $redis;
    }

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
}
