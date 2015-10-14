<?php

namespace Resque\Component\Worker\Factory;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Core\Exception\ResqueRuntimeException;
use Resque\Component\Core\Process;
use Resque\Component\Job\Factory\JobInstanceFactoryInterface;
use Resque\Component\Queue\Factory\QueueFactoryInterface;
use Resque\Component\System\SystemInterface;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\Worker;

class WorkerFactory implements WorkerFactoryInterface
{
    /**
     * @var QueueFactoryInterface
     */
    protected $queueFactory;

    /**
     * @var JobInstanceFactoryInterface
     */
    protected $jobInstanceFactory;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var SystemInterface
     */
    protected $system;

    /**
     * Constructor
     *
     * @param QueueFactoryInterface $queueFactory
     * @param JobInstanceFactoryInterface $jobFactory
     * @param EventDispatcherInterface $eventDispatcher
     * @param SystemInterface $system
     */
    public function __construct(
        QueueFactoryInterface $queueFactory,
        JobInstanceFactoryInterface $jobFactory,
        EventDispatcherInterface $eventDispatcher,
        SystemInterface $system
    ) {
        $this->queueFactory = $queueFactory;
        $this->jobInstanceFactory = $jobFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->system = $system;
    }

    /**
     * {@inheritDoc}
     */
    public function createWorker()
    {
        $worker = new Worker(
            $this->jobInstanceFactory,
            $this->eventDispatcher
        );

        $worker->setHostname($this->system->getHostname());

        return $worker;
    }

    /**
     * {@inheritDoc}
     */
    public function createWorkerFromId($workerId)
    {
        if (false === strpos($workerId, ":")) {
            return null;
        }

        list($hostname, $pid, $queues) = explode(':', $workerId, 3);
        $queues = explode(',', $queues);

        if(!$queues){
            throw new ResqueRuntimeException(sprintf("Invalid worker ID \"%s\"", $workerId));
        }

        $worker = new Worker(
            $this->jobInstanceFactory,
            $this->eventDispatcher
        );
        $worker->setHostname($hostname);

        $process = new Process($pid); // @todo When worker is on another host, Process is over kill.
        $worker->setProcess($process);

        foreach ($queues as $queue) {
            $worker->addQueue($this->queueFactory->createQueue($queue)); // @todo what about wildcard queues? :(
        }

        return $worker;
    }
}
