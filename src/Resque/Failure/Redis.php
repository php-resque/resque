<?php

namespace Resque\Failure;

use Predis\ClientInterface;
use Resque\Job;
use Resque\WorkerInterface;
use Resque\QueueInterface;

/**
 * Default redis backend for storing failed jobs.
 */
class Redis implements FailureInterface
{
    public function __construct(ClientInterface $redis)
    {
        $this->redis = $redis;
    }

    public function save(Job $job, \Exception $exception, QueueInterface $queue, WorkerInterface $worker)
    {
        $this->redis->rpush(
            'failed',
            json_encode(
                array(
                    'failed_at' => strftime('%a %b %d %H:%M:%S %Z %Y'),
                    'payload' => $job,
                    'exception' => get_class($exception),
                    'error' => $exception->getMessage(),
                    'backtrace' => explode("\n", $exception->getTraceAsString()),
                    'worker' => $worker->getId(),
                    'queue' => $queue->getName(),
                )
            )
        );
    }

    public function count()
    {

    }

    public function all()
    {

    }

    public function clear()
    {

    }
}
