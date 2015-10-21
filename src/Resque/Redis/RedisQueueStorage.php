<?php

namespace Resque\Redis;

use Resque\Component\Job\Model\FilterableJobInterface;
use Resque\Component\Job\Model\Job;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Model\OriginQueueAwareInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Queue\Storage\QueueStorageInterface;

/**
 * Resque Redis queue
 *
 * Uses redis to store the queue.
 */
class RedisQueueStorage implements
    RedisClientAwareInterface,
    QueueStorageInterface
{
    /**
     * @var RedisClientInterface A redis connection.
     */
    protected $redis;

    /**
     * Constructor.
     *
     * @param RedisClientInterface $redis
     */
    public function __construct(RedisClientInterface $redis)
    {
        if (null !== $redis) {
            $this->setRedisClient($redis);
        }
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
     * Get redis key.
     *
     * @param QueueInterface $queue A the queue to the get the Resque Redis key name for.
     * @return string Key name for Redis.
     */
    protected function getRedisKey(QueueInterface $queue)
    {
        return 'queue:' . $queue->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function enqueue(QueueInterface $queue, JobInterface $job)
    {
        $result = $this->redis->rpush(
            $this->getRedisKey($queue),
            $job->encode()
        );

        return $result === 1;
    }

    /**
     * {@inheritDoc}
     */
    public function dequeue(QueueInterface $queue)
    {
        $payload = $this->redis->lpop($this->getRedisKey($queue));

        if (!$payload) {
            return null;
        }

        $job = Job::decode($payload); // @todo should be something like $this->jobEncoderThingy->decode()

        if ($job instanceof OriginQueueAwareInterface) {
            $job->setOriginQueue($queue);
        }

        return $job;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(QueueInterface $queue, $filter = array())
    {
        $jobsRemoved = 0;

        $queueKey = $this->getRedisKey($queue);
        $tmpKey = $queueKey . ':removal:' . time() . ':' . uniqid();
        $enqueueKey = $tmpKey . ':enqueue';

        // Move each job from original queue to a temporary list and leave
        while (\true) {
            $payload = $this->redis->rpoplpush($queueKey, $tmpKey);
            if (!empty($payload)) {
                $job = Job::decode($payload); // @todo should be something like $this->jobEncoderThingy->decode()
                if ($job instanceof FilterableJobInterface && $job::matchFilter($job, $filter)) {
                    $this->redis->rpop($tmpKey);
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
     * {@inheritDoc}
     */
    public function count(QueueInterface $queue)
    {
        return $this->redis->llen($this->getRedisKey($queue));
    }
}
