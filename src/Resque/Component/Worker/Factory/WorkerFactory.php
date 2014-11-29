<?php

namespace Resque\Component\Worker\Factory;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Job\Factory\JobInstanceFactoryInterface;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\Worker;

class WorkerFactory implements WorkerFactoryInterface
{
    /**
     * @var JobInstanceFactoryInterface
     */
    protected $jobInstanceFactory;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    /**
     * Constructor
     *
     * @param JobInstanceFactoryInterface $jobFactory
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        JobInstanceFactoryInterface $jobFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->jobInstanceFactory = $jobFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Create worker
     *
     * @return WorkerInterface
     */
    public function createWorker()
    {
        return new Worker(
            $this->jobInstanceFactory,
            $this->eventDispatcher
        );
    }
}
