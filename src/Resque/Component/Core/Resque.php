<?php

namespace Resque\Component\Core;

use Resque\Component\Job\Model\Job;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\Model\TrackableJobInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Queue\Registry\QueueRegistryInterface;

/**
 * Resque
 */
class Resque
{
    const VERSION = 'dev';

    /**
     * @var QueueRegistryInterface  The queue registry
     */
    protected $queueRegistry;

    /**
     * Constructor
     *
     * @param QueueRegistryInterface $queueRegistry The queue registry and factory
     */
    public function __construct(QueueRegistryInterface $queueRegistry)
    {
        $this->queueRegistry = $queueRegistry;
    }

    /**
     * Enqueue
     *
     * Enqueues a job.
     *
     * @param string $queueName The name of the queue the job should go in.
     * @param string $jobClass The FQCN of your targeted job class.
     * @param array $arguments The arguments to pass to $jobClass->perform().
     *
     * @return JobInterface The job instance that was created if enqueue was successful, exception otherwise.
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
        return $this->queueRegistry->createQueue($queueName);
    }
}
