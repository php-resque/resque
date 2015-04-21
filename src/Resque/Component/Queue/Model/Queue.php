<?php

namespace Resque\Component\Queue\Model;

use Resque\Component\Queue\Storage\QueueStorageInterface;
use Resque\Component\Queue\ResqueQueueEvents;
use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Event\QueueJobEvent;

/**
 * Queue
 */
class Queue implements QueueInterface
{
    /**
     * @var string The name of the queue.
     */
    protected $name;

    /**
     * @var QueueStorageInterface
     */
    protected $storage;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Constructor.
     *
     * @param string $name
     * @param QueueStorageInterface $storage
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct($name, QueueStorageInterface $storage, EventDispatcherInterface $eventDispatcher)
    {
        $this->storage = $storage;
        $this->eventDispatcher = $eventDispatcher;
        $this->setName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function enqueue(JobInterface $job)
    {
        $this->eventDispatcher->dispatch(ResqueQueueEvents::JOB_PUSH, new QueueJobEvent($this, $job));

        if ($this->storage->enqueue($this, $job)) {
            $this->eventDispatcher->dispatch(ResqueQueueEvents::JOB_PUSHED, new QueueJobEvent($this, $job));

            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function dequeue()
    {
        $job = $this->storage->dequeue($this);

        if (!$job) {
            return null;
        }

        if ($job instanceof OriginQueueAwareInterface) {
            $job->setOriginQueue($this);
        }

        $this->eventDispatcher->dispatch(ResqueQueueEvents::JOB_POPPED, new QueueJobEvent($this, $job));

        return $job;
    }

    /**
     * {@inheritDoc}
     */
    public function remove($filter = array())
    {
        return $this->storage->remove($this, $filter);
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return $this->storage->count($this);
    }

    public function __toString()
    {
        return $this->getName();
    }
}
