<?php

namespace Resque;

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
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Push a job to the end of a specific queue. If the queue does not
     * exist, then create it as well.
     *
     * // @todo throw a exception when it fails!
     *
     * @param Job $job The Job to enqueue.
     * @return bool True if successful, false otherwise.
     */
    public function push(Job $job)
    {
        Resque::redis()->sadd('queues', $this->name);

        $length = Resque::redis()->rpush(
            'queue:' . $this->name,
            json_encode($job->jsonSerialize())
        );

        if ($length < 1) {
            return false;
        }

        return true;
    }

    /**
     * Pop a job off the end of the specified queue, decode it and return it.
     *
     * @return Job|null Decoded job from the queue, or null if no jobs.
     */
    public function pop()
    {
        $item = Resque::redis()->lpop('queue:' . $this);

        if (!$item) {
            return null;
        }

        $payload = json_decode($item, true);

        // @todo check for json_decode error, if error throw an exception.

        $job = new Job($payload['class'], $payload['args'][0]);

        $job->setQueue($this);

        return $job;

    }

    /**
     * Pop an item off the end of the specified queues, using blocking list pop,
     * decode it and return it.
     *
     * @deprecated
     *
     * @param array $queues
     * @param int $timeout
     * @return null|array   Decoded item from the queue.
     */
    public function blpop(array $queues, $timeout)
    {
        $list = array();
        foreach ($queues AS $queue) {
            $list[] = 'queue:' . $queue;
        }

        $item = Resque::redis()->blpop($list, (int)$timeout);

        if (!$item) {
            return;
        }

        /**
         * Normally the Resque_Redis class returns queue names without the prefix
         * But the blpop is a bit different. It returns the name as prefix:queue:name
         * So we need to strip off the prefix:queue: part
         */
        $queue = substr($item[0], strlen(Resque::redis()->getPrefix() . 'queue:'));

        return array(
            'queue' => $queue,
            'payload' => json_decode($item[1], true)
        );
    }

    /**
     * Return the size (number of pending jobs) of the specified queue.
     *
     * @param string $queue name of the queue to be checked for pending jobs
     *
     * @return int The size of the queue.
     */
    public function size($queue)
    {
        return Resque::redis()->llen('queue:' . $queue);
    }

    /**
     * Enqueues a job
     *
     * @param Job $job The job to enqueue
     * @param string $class The name of the class that contains the code to execute the job.
     * @param array $args Any optional arguments that should be passed when the job is executed.
     * @param boolean $trackStatus Set to true to be able to monitor the status of a job.
     *
     * @return string
     */
    public function enqueue(Job $job)
    {
        return $this->push($job);
        $result = Job::create($queue, $class, $args, $trackStatus);
        if ($result) {
            Resque_Event::trigger(
                'afterEnqueue',
                array(
                    'class' => $class,
                    'args' => $args,
                    'queue' => $queue,
                    'id' => $result,
                )
            );
        }

        return $result;
    }

    /**
     * Get an array of all known queues.
     *
     * @return array Array of queues.
     */
    public function queues()
    {
        $queues = Resque::redis()->smembers('queues');
        if (!is_array($queues)) {
            $queues = array();
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
