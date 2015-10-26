<?php

namespace Resque\Component\Queue\Registry;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Queue\Event\QueueEvent;
use Resque\Component\Queue\Factory\QueueFactoryInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Queue\ResqueQueueEvents;

/**
 * Queue registry
 */
class QueueRegistry implements
    QueueRegistryInterface,
    QueueFactoryInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var QueueFactoryInterface
     */
    protected $factory;

    /**
     * @var QueueRegistryAdapterInterface
     */
    protected $adapter;

    /**
     * Constructor
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param QueueRegistryAdapterInterface $adapter
     * @param QueueFactoryInterface $factory
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        QueueRegistryAdapterInterface $adapter,
        QueueFactoryInterface $factory
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->adapter = $adapter;
        $this->factory = $factory;
    }

    /**
     * {@inheritDoc}
     */
    public function register(QueueInterface $queue)
    {
        $this->adapter->save($queue);

        $this->eventDispatcher->dispatch(ResqueQueueEvents::REGISTERED, new QueueEvent($queue));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isRegistered(QueueInterface $queue)
    {
        return $this->adapter->has($queue);
    }

    /**
     * {@inheritDoc}
     */
    public function deregister(QueueInterface $queue)
    {
        $result = $this->adapter->delete($queue);

        if ($result) {
            $this->eventDispatcher->dispatch(ResqueQueueEvents::UNREGISTERED, new QueueEvent($queue));
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function all()
    {
        $queues = array();

        foreach ($this->adapter->all() as $queueName) {
            $queues[$queueName] = $this->createQueue($queueName);
        }

        return $queues;
    }

    /**
     * {@inheritDoc}
     */
    public function createQueue($name)
    {
        return $this->factory->createQueue($name);
    }
}
