<?php

namespace Resque\Failure;

use Predis\ClientInterface;
use Resque\Job\JobInterface;
use Resque\Job\QueueAwareJobInterface;
use Resque\QueueInterface;
use Resque\WorkerInterface;

/**
 * Default redis backend for storing failed jobs.
 */
class Redis implements FailureInterface
{
    /**
     * @var ClientInterface A redis client.
     */
    protected $redis;

    public function __construct(ClientInterface $redis)
    {
        $this->redis = $redis;
    }

    public function save(JobInterface $job, \Exception $exception, WorkerInterface $worker)
    {
        $queue = ($job instanceof QueueAwareJobInterface) ? $job->getOriginQueue() : null;

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
                    'queue' => ($queue instanceof QueueInterface) ? $queue->getName() : null,
                )
            )
        );
    }

    public function count()
    {
        return $this->redis->llen('failed');
    }

    public function clear()
    {
        $this->redis->del('failed');
    }
}
