<?php

namespace Resque;

use Predis\ClientInterface;
use Resque\Component\Core\RedisAwareInterface;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Model\AbstractQueue;
use Resque\Component\Queue\Model\OriginQueueAwareInterface;
use Resque\Job\FilterAwareJobInterface;

/**
 * Resque Redis queue
 *
 * Uses redis to store the queue.
 */
class Queue extends AbstractQueue implements RedisAwareInterface
{
    /**
     * @var ClientInterface A Predis Redis connection.
     */
    protected $redis;

    /**
     * @param string $name The name of the queue
     * @param ClientInterface $redis
     */
    public function __construct($name, ClientInterface $redis = null)
    {
        $this->setName($name);
        if (null !== $redis) {
            $this->setRedisClient($redis);
        }
    }

    public function setRedisClient(ClientInterface $redis)
    {
        $this->redis = $redis;

        return $this;
    }

    /**
     * @return string Key name for redis.
     */
    protected function getRedisKey()
    {
        return 'queue:' . $this->getName();
    }

    /**
     * Registers this queue in redis
     *
     * @return $this
     */
    public function register()
    {
        $this->redis->sadd('queues', $this->getName());

        return $this;
    }

    /**
     * Removes the queue and the jobs with in it
     *
     * @return integer The number of jobs removed
     */
    public function deregister()
    {
        $this->redis->multi();
        $this->redis->llen($this->getRedisKey());
        $this->redis->del($this->getRedisKey());
        $this->redis->srem('queues', $this->getName());
        $responses = $this->redis->exec();
        return isset($responses[0]) ? $responses[0] : 0;
    }

    /**
     * Push a job into the queue
     *
     * It will also make sure the queue is registered.
     *
     * @todo throw a exception when it fails!
     *
     * @param JobInterface $job The Job to enqueue.
     * @return bool True if successful, false otherwise.
     */
    public function push(JobInterface $job)
    {
        $this->register();

        $this->redis->rpush(
            $this->getRedisKey(),
            $job::encode($job)
        );

        return true;
    }

    /**
     * Pop a job from the queue
     *
     * @return JobInterface|null Decoded job from the queue, or null if no jobs.
     */
    public function pop()
    {
        $payload = $this->redis->lpop($this->getRedisKey());

        if (!$payload) {
            return null;
        }

        $job = Job::decode($payload); // @todo should be something like $this->jobEncoderThingy->decode()

        if ($job instanceof OriginQueueAwareInterface) {
            $job->setOriginQueue($this);
        }

        return $job;
    }

    /**
     * Remove jobs matching the filter
     *
     * @param array $filter
     * @return int The number of jobs removed
     */
    public function remove($filter = array())
    {
        $jobsRemoved = 0;

        $queueKey = $this->getRedisKey();
        $tmpKey = $queueKey . ':removal:' . time() . ':' . uniqid();
        $enqueueKey = $tmpKey . ':enqueue';

        // Move each job from original queue to a temporary list and process it
        while (\true) {
            $payload = $this->redis->rpoplpush($queueKey, $tmpKey);
            if (!empty($payload)) {
                $job = Job::decode($payload); // @todo should be something like $this->jobEncoderThingy->decode()
                if ($job instanceof FilterAwareJobInterface && $job::matchFilter($job, $filter)) {
                    $jobsRemoved++;
                } else {
                    $this->redis->rpoplpush($tmpKey, $enqueueKey);
                }
            } else {
                break;
            }
        }

        // Move back from enqueue list to original queue
        while (\true) {
            $payload = $this->redis->rpoplpush($enqueueKey, $queueKey);
            if (empty($payload)) {
                break;
            }
        }

        $this->redis->del($tmpKey);
        $this->redis->del($enqueueKey);

        return $jobsRemoved;
    }

    /**
     * Get an array of all known queues.
     *
     * @deprecated Should be named something else like "allRegisterQueues", possibly not exist here either.
     *
     * @return self[] Array of queues, keyed by queue name.
     */
    public function all()
    {
        $queuesNames = $this->redis->smembers('queues');
        $queues = array();

        foreach ($queuesNames as $queueName) {
            $queues[$queueName] = new self($queueName, $this->redis);
        }

        return $queues;
    }

    /**
     * Return the number of pending jobs in the queue
     *
     * @return int The size of the queue.
     */
    public function count()
    {
        return $this->redis->llen($this->getRedisKey());
    }

    public function __toString()
    {
        return $this->getName();
    }
}
