<?php

namespace Resque\Redis;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Core\Exception\ResqueRuntimeException;
use Resque\Component\Queue\Model\OriginQueueAwareInterface;
use Resque\Component\Worker\Event\WorkerEvent;
use Resque\Component\Worker\Factory\WorkerFactoryInterface;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\Registry\WorkerRegistryInterface;
use Resque\Component\Worker\ResqueWorkerEvents;
use Resque\Redis\Bridge\PredisBridge;

/**
 * Resque redis worker registry
 */
class RedisWorkerRegistry implements
    WorkerRegistryInterface,
    RedisClientAwareInterface
{
    /**
     * @var WorkerFactoryInterface
     */
    protected $workerFactory;

    /**
     * @var RedisClientInterface Redis connection.
     */
    protected $redis;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        RedisClientInterface $redis,
        EventDispatcherInterface $eventDispatcher,
        WorkerFactoryInterface $workerFactory
    ) {
        $this->setRedisClient($redis);
        $this->eventDispatcher = $eventDispatcher;
        $this->workerFactory = $workerFactory;
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
    public function register(WorkerInterface $worker)
    {
        $id = $worker->getId();
        if ($this->isRegistered($worker)) {
            throw new ResqueRuntimeException(sprintf(
                'Cannot double register worker %s, deregister it before calling register again',
                $id
            ));
        }

        $this->redis->sadd('workers', $id);
        $this->redis->set('worker:' . $id . ':started', date('c'));

        $this->eventDispatcher->dispatch(ResqueWorkerEvents::REGISTERED, new WorkerEvent($worker));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isRegistered(WorkerInterface $worker)
    {
        return $this->redis->sismember('workers', $worker->getId());
    }

    /**
     * {@inheritDoc}
     */
    public function deregister(WorkerInterface $worker)
    {
        $id = $worker->getId();

        if (!$this->redis->srem('workers', $id)) {
            // @todo log warning or info, and return?
        }
        $this->redis->del('worker:' . $id);
        $this->redis->del('worker:' . $id . ':started');

        $this->eventDispatcher->dispatch(ResqueWorkerEvents::UNREGISTERED, new WorkerEvent($worker));

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

        $instances = array();
        foreach ($workerIds as $workerId) {
            $instances[] = $this->workerFactory->createWorkerFromId($workerId);
        }

        return $instances;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return $this->redis->scard('workers');
    }

    /**
     * {@inheritDoc}
     */
    public function findWorkerById($workerId)
    {
        $worker = $this->workerFactory->createWorkerFromId($workerId);

        if (false === $this->isRegistered($worker)) {

            return null;
        }

        return $worker;
    }

    /**
     * {@inheritDoc}
     */
    public function persist(WorkerInterface $worker)
    {
        $currentJob = $worker->getCurrentJob();

        if (null === $currentJob) {
            $this->redis->del('worker:' . $worker->getId());

            $this->eventDispatcher->dispatch(ResqueWorkerEvents::PERSISTED, new WorkerEvent($worker));

            return $this;
        }

        $payload = json_encode(
            array(
                'queue' => ($currentJob instanceof OriginQueueAwareInterface) ? $currentJob->getOriginQueue() : null,
                'run_at' => date('c'),
                'payload' => $currentJob->encode(),
            )
        );

        $this->redis->set('worker:' . $worker->getId(), $payload);

        $this->eventDispatcher->dispatch(ResqueWorkerEvents::PERSISTED, new WorkerEvent($worker));

        return $this;
    }
}
