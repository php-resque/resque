<?php

namespace Resque\Redis;

use Resque\Component\Queue\Model\OriginQueueAwareInterface;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\Registry\WorkerRegistryAdapterInterface;

/**
 * Resque redis worker registry adapter.
 */
class RedisWorkerRegistryAdapter implements
    WorkerRegistryAdapterInterface,
    RedisClientAwareInterface
{
    /**
     * @var RedisClientInterface A Redis client.
     */
    protected $redis;

    /**
     * Constructor.
     *
     * @param RedisClientInterface $redis A Redis client.
     */
    public function __construct(RedisClientInterface $redis)
    {
        $this->setRedisClient($redis);
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
     * {@inheritDoc}
     */
    public function save(WorkerInterface $worker)
    {
        $currentJob = $worker->getCurrentJob();

        $this->redis->sadd('workers', $worker->getId());
        $this->redis->set('worker:' . $worker->getId() . ':started', date('c')); // @todo worker->getStartedAt()

        if ($currentJob) {
            $payload = json_encode(
                array(
                    'queue' => ($currentJob instanceof OriginQueueAwareInterface) ? $currentJob->getOriginQueue() : null,
                    'run_at' => date('c'), // @todo currentJob->getRunAt
                    'payload' => $currentJob->encode(),
                )
            );

            $this->redis->set('worker:' . $worker->getId(), $payload);
        } else {
            $this->redis->del('worker:' . $worker->getId());
        }

        // @todo use multi -> exec or something.
    }

    /**
     * {@inheritDoc}
     */
    public function has(WorkerInterface $worker)
    {
        return $this->redis->sismember('workers', $worker->getId()) == 1 ? true : false;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(WorkerInterface $worker)
    {
        $id = $worker->getId();

        $this->redis->srem('workers', $id);
        $this->redis->del('worker:' . $id);
        $this->redis->del('worker:' . $id . ':started');

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function all()
    {
        $workerIds = $this->redis->smembers('workers');

        if (!is_array($workerIds)) {
            return array();
        }

        return $workerIds;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return $this->redis->scard('workers');
    }
}
