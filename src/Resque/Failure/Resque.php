<?php

namespace Resque\Failure;

use Predis\ClientInterface;
use Resque\Job;
use Resque\WorkerInterface;
use Resque\QueueInterface;

/**
 * Default redis backend for storing failed jobs.
 */
class Resque implements FailureInterface
{
    public function __construct(ClientInterface $redis)
    {
        $this->redis = $redis;
    }

    public function save(Job $job, \Exception $exception, QueueInterface $queue, WorkerInterface $worker)
    {
        $data = new \stdClass;
        $data->failed_at = strftime('%a %b %d %H:%M:%S %Z %Y');
        $data->payload = $job;
        $data->exception = get_class($exception);
        $data->error = $exception->getMessage();
        $data->backtrace = explode("\n", $exception->getTraceAsString());
        $data->worker = $worker->getId();
        $data->queue = $queue->getName();
        $data = json_encode($data);

        $this->redis->rpush('failed', $data);
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
