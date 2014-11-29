<?php

namespace Resque\Component\Core;

use Predis\ClientInterface;
use Resque\Component\Queue\Model\OriginQueueAwareInterface;
use Resque\Component\Worker\Factory\WorkerFactoryInterface;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\Registry\WorkerRegistryInterface;
use Resque\Component\Worker\Worker;

/**
 * Resque redis worker registry
 */
class RedisWorkerRegistry implements WorkerRegistryInterface, RedisAwareInterface
{
    /**
     * @var array Workers currently registered in Redis as work() has been called.
     */
    protected $registeredWorkers;

    /**
     * @var WorkerFactoryInterface
     */
    protected $workerFactory;

    /**
     * @var ClientInterface Redis connection.
     */
    protected $redis;

    public function __construct(ClientInterface $redis, WorkerFactoryInterface $workerFactory)
    {
        $this->setRedisClient($redis);
        $this->workerFactory = $workerFactory;
        $this->registeredWorkers = array();
    }

    /**
     * {@inheritDoc}
     */
    public function setRedisClient(ClientInterface $redis)
    {
        $this->redis = $redis;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function register(WorkerInterface $worker)
    {
        if (in_array($worker, $this->registeredWorkers, true)) {
            throw new \Exception('Cannot double register a worker, call deregister(), or halt() to clear');
        }

        $id = $worker->getId();

        $this->registeredWorkers[$id] = $worker;

        $this->redis->sadd('workers', $id);
        $this->redis->set('worker:' . $id . ':started', date('c'));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isRegistered(WorkerInterface $worker)
    {
        return (bool)$this->redis->sismember('workers', $worker->getId());
    }

    /**
     * {@inheritDoc}
     */
    public function deregister(WorkerInterface $worker)
    {
        $id = $worker->getId();

        $worker->shutdownNow();

        $this->redis->srem('workers', $id);
        $this->redis->del('worker:' . $id);
        $this->redis->del('worker:' . $id . ':started');

        $worker->clearStats();

        unset($this->registeredWorkers[$id]);
    }

    /**
     * {@inheritDoc}
     */
    public function all()
    {
        $workers = $this->redis->smembers('workers');

        if (!is_array($workers)) {
            $workers = array();
        }

        $instances = array();
        foreach ($workers as $workerId) {
            $instances[] = $this->findWorkerById($workerId);
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
     * Given a worker ID, find it and return an instantiated worker class for it.
     *
     * @param string $workerId The ID of the worker.
     * @return Worker Instance of the worker. null if the worker does not exist.
     */
    public function findWorkerById($workerId)
    {
        if (false === strpos($workerId, ":")) {

            return null;
        }

        list($hostname, $pid, $queues) = explode(':', $workerId, 3);
        $queues = explode(',', $queues);

        $worker = $this->workerFactory->createWorker();

        $worker->setId($workerId);
        // @todo use queue factory
//        foreach ($queues as $queue) {
//            $worker->addQueue(new RedisQueue($queue));
//        }

        if (false === $this->isRegistered($worker)) {

            return null;
        }

        return $worker;
    }

    /**
     * Save worker state
     *
     * @param WorkerInterface $worker The worker persist state to the registry. It must be registered.
     * @return $this
     */
    public function persist(WorkerInterface $worker)
    {
        $currentJob = $worker->getCurrentJob();

        if (null === $currentJob) {
            $this->redis->del('worker:' . $worker->getId());

            return;
        }

        $payload = json_encode(
            array(
                'queue' => ($currentJob instanceof OriginQueueAwareInterface) ? $currentJob->getOriginQueue() : null,
                'run_at' => date('c'),
                'payload' => $currentJob::encode($currentJob),
            )
        );

        $this->redis->set('worker:' . $worker->getId(), $payload);
    }
}
