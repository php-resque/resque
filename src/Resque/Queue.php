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

    /**
     * Registers this queue in redis
     *
     * @return $this
     */
    public function register()
    {
        $this->redis->sadd('queues', $this->name);

        return $this;
    }

    /**
     * Removes the queue and the jobs with in it.
     *
     * @return integer The number of jobs removed
     */
    public function unregister()
    {
        $this->redis->multi();
        $this->redis->llen('queue:' . $this->name);
        $this->redis->del('queue:' . $this->name);
        $this->redis->srem('queues', $this->name);
        $responses = $this->redis->exec();
        return isset($responses[0]) ? $responses[0] : null;
    }

    /**
     * Push a job into the queue.
     *
     * If the queue does not exist, then create it as well.
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
            'queue:' . $this->name,
            json_encode($job->jsonSerialize())
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
     * Pop a job from the front of the queue
     *
     * @return Job|null Decoded job from the queue, or null if no jobs.
     */
    public function pop()
    {
        $item = $this->redis->lpop('queue:' . $this);

        if (!$item) {
            return null;
        }

        $payload = json_decode($item, true);

        // @todo check for json_decode error, if error throw an exception.

        $job = new Job($payload['class'], $payload['args'][0]);
        $job->setId($payload['id']);
        $job->setQueue($this);

        return $job;
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

    /**
     * Get an array of all known queues.
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

    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->getName();
    }
}
