<?php

namespace Resque;

use Predis\ClientInterface;

/**
 * Resque Queue
 *
 * Provides the ability to push and pull jobs from a queue, along side other utility functions.
 */
class Queue implements QueueInterface
{
    /**
     * @var string The name of the queue.
     */
    protected $name;

    /**
     * @var ClientInterface Redis connection.
     */
    protected $redis;

    /**
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param ClientInterface $redis
     * @return $this
     */
    public function setRedisBackend(ClientInterface $redis)
    {
        $this->redis = $redis;

        return $this;
    }

    public function getRedisKey()
    {
        return 'queue:' . $this->getName();
    }

    public function getName()
    {
        return $this->name;
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
    public function unregister()
    {
        $this->redis->multi();
        $this->redis->llen($this->getRedisKey());
        $this->redis->del($this->getRedisKey());
        $this->redis->srem('queues', $this->getName());
        $responses = $this->redis->exec();
        return isset($responses[0]) ? $responses[0] : null;
    }

    /**
     * Push a job into the queue
     *
     * It will also make sure the queue is registered.
     *
     * @todo throw a exception when it fails!
     *
     * @param Job $job The Job to enqueue.
     * @return bool True if successful, false otherwise.
     */
    public function push(Job $job)
    {
        $this->register();

        $this->redis->rpush(
            $this->getRedisKey(),
            $job::encode($job)
        );

//        if ($result) {
//            Resque_Event::trigger(
//                'afterEnqueue',
//                array(
//                    'class' => $class,
//                    'args' => $args,
//                    'queue' => $queue,
//                    'id' => $result,
//                )
//            );
//        }

        return true;
    }

    /**
     * Pop a job from the queue
     *
     * @return Job|null Decoded job from the queue, or null if no jobs.
     */
    public function pop()
    {
        $item = $this->redis->lpop('queue:' . $this);

        if (!$item) {
            return null;
        }

        $job = Job::decode($item);
        $job->setQueue($this);

        return $job;
    }

    /**
     * Remove jobs matching the filter
     *
     *
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
                if ($job::matchFilter($job, $filter)) {
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
     * @deprecated should be named something, possibly not exist here either.
     *
     * @return self[] Array of queues.
     */
    public function all()
    {
        $queuesNames = $this->redis->smembers('queues');
        $queues = array();

        foreach ($queuesNames as $queueName) {
            $queues[$queueName] = new self($queueName);
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
        return $this->redis->llen('queue:' . $this);
    }

    public function __toString()
    {
        return $this->getName();
    }
}
