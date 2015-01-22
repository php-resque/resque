<?php

namespace Resque\Component\Worker\Factory;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Core\Process;
use Resque\Component\Job\Factory\JobInstanceFactoryInterface;
use Resque\Component\Queue\Factory\QueueFactoryInterface;
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
     * Constructor
     *
     * @param QueueFactoryInterface $queueFactory
     * @param JobInstanceFactoryInterface $jobFactory
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        QueueFactoryInterface $queueFactory,
        JobInstanceFactoryInterface $jobFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->queueFactory = $queueFactory;
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
        $worker = new Worker(
            $this->jobInstanceFactory,
            $this->eventDispatcher
        );

        if (function_exists('gethostname')) {
            $hostname = gethostname();
        } else {
            $hostname = php_uname('n');
        }

        $worker->setHostname($hostname);

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

        $worker = new Worker(
            $this->jobInstanceFactory,
            $this->eventDispatcher
        );
        $process = new Process();
        $process->setPid($pid);
        $worker->setProcess($process);
        $worker->setHostname($hostname);
        foreach ($queues as $queue) {
            $worker->addQueue($this->queueFactory->createQueue($queue)); // @todo what about wildcard queues? :(
        }

        return $worker;
    }
}
