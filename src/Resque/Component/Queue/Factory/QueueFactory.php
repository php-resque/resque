<?php

namespace Resque\Component\Queue\Factory;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Queue\Model\Queue;
use Resque\Component\Queue\Storage\QueueStorageInterface;

class QueueFactory implements
    QueueFactoryInterface
{
    /**
     * @var QueueStorageInterface
     */
    protected $storage;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Constructor
     *
     * @param QueueStorageInterface $storage
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(QueueStorageInterface $storage, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->storage = $storage;
    }

    /**
     * {@inheritDoc}
     */
    public function createQueue($name)
    {
        return new Queue(
            $name,
            $this->storage,
            $this->eventDispatcher
        );
    }
}
