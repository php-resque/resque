<?php

namespace Resque;

use Predis\ClientInterface;
use Resque\Queue\QueueInterface;

/**
 * Resque
 */
class Resque
{
    const VERSION = 'dev';

    /**
     * @var ClientInterface Redis connection.
     */
    protected $redis;

    /**
     * Constructor
     *
     * @param ClientInterface $redis A connection to Redis.
     */
    public function __construct(ClientInterface $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Enqueue
     *
     * Enqueue a job.
     *
     * @param string $queueName The name of the queue the job should go in.
     * @param string $jobClass The FQCN of your targeted job class.
     * @param array $arguments
     * @return Job\JobInterface The job instance that was created if enqueue was successful, exception otherwise.
     */
    public function enqueue($queueName, $jobClass, $arguments = array())
    {
        $queue = $this->getQueue($queueName);
        $job = new Job($jobClass, $arguments);
        $queue->push($job);

        return $job;
    }

    /**
     * Get queue
     *
     * Creates a QueueInterface for the given queue name.
     *
     * @param string $queueName
     * @return QueueInterface
     */
    public function getQueue($queueName)
    {
        $queue = new Queue($queueName);
        $queue->setRedisBackend($this->redis);

        return $queue;
    }
}
