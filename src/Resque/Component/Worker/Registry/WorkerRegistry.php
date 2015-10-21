<?php

namespace Resque\Component\Worker\Registry;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Core\Exception\ResqueRuntimeException;
use Resque\Component\Worker\Event\WorkerEvent;
use Resque\Component\Worker\Factory\WorkerFactoryInterface;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\ResqueWorkerEvents;

/**
 * Resque redis worker registry
 */
class WorkerRegistry implements
    WorkerRegistryInterface
{
    /**
     * @var WorkerRegistryAdapterInterface
     */
    protected $adapter;

    /**
     * @var WorkerFactoryInterface
     */
    protected $workerFactory;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Constructor.
     *
     * @param WorkerRegistryAdapterInterface $adapter
     * @param EventDispatcherInterface $eventDispatcher
     * @param WorkerFactoryInterface $workerFactory
     */
    public function __construct(
        WorkerRegistryAdapterInterface $adapter,
        EventDispatcherInterface $eventDispatcher,
        WorkerFactoryInterface $workerFactory
    ) {
        $this->adapter = $adapter;
        $this->eventDispatcher = $eventDispatcher;
        $this->workerFactory = $workerFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function register(WorkerInterface $worker)
    {
        if ($this->isRegistered($worker)) {
            throw new ResqueRuntimeException(sprintf(
                'Cannot double register worker "%s", deregister it before calling register again.',
                $worker->getId()
            ));
        }

        $this->adapter->save($worker);

        $this->eventDispatcher->dispatch(ResqueWorkerEvents::REGISTERED, new WorkerEvent($worker));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isRegistered(WorkerInterface $worker)
    {
        return $this->adapter->has($worker);
    }

    /**
     * {@inheritDoc}
     */
    public function deregister(WorkerInterface $worker)
    {
        $this->adapter->delete($worker);

        $this->eventDispatcher->dispatch(ResqueWorkerEvents::UNREGISTERED, new WorkerEvent($worker));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function all()
    {
        $workerIds = $this->adapter->all();

        if (!is_array($workerIds)) {
            return array();
        }

        /** @var WorkerInterface[] $instances */
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
        return $this->adapter->count();
    }

    /**
     * {@inheritDoc}
     */
    public function findWorkerById($workerId)
    {
        $worker = $this->workerFactory->createWorkerFromId($workerId);

        if ($this->adapter->has($worker)) {
            return $worker;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function persist(WorkerInterface $worker)
    {
        $this->adapter->save($worker);

        $this->eventDispatcher->dispatch(ResqueWorkerEvents::PERSISTED, new WorkerEvent($worker));

        return $this;
    }
}
