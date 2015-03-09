<?php

namespace Resque\Component\Queue\Registry;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Queue\Event\QueueEvent;
use Resque\Component\Queue\Factory\QueueFactoryInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Queue\ResqueQueueEvents;

/**
 * Resque queue registry
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
     * Constructor
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param QueueRegistryInterface $adapter
     * @param QueueFactoryInterface $factory
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        QueueRegistryInterface $adapter,
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
        $this->adapter->register($queue);

        $this->eventDispatcher->dispatch(ResqueQueueEvents::REGISTERED, new QueueEvent($queue));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isRegistered(QueueInterface $queue)
    {
        return $this->adapter->isRegistered($queue);
    }

    /**
     * {@inheritDoc}
     */
    public function deregister(QueueInterface $queue)
    {
        $result = $this->adapter->deregister($queue);

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
        return $this->adapter->all();
    }

    /**
     * {@inheritDoc}
     */
    public function createQueue($name)
    {
        return $this->factory->createQueue($name);
    }
}
