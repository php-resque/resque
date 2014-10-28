<?php

namespace Resque\Failure;

use Predis\ClientInterface;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Model\OriginQueueAwareInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Worker\Model\WorkerInterface;

/**
 * Default redis backend for storing failed jobs.
 */
class RedisFailure implements FailureInterface
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
        $queue = ($job instanceof OriginQueueAwareInterface) ? $job->getOriginQueue() : null;

        $this->redis->rpush(
            'failed',
            json_encode(
                array(
                    'failed_at' => date('c'),
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
