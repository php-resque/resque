<?php

namespace Resque\Component\Worker\Factory;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Core\Exception\ResqueRuntimeException;
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

    public function getHostname(){
        if (function_exists('gethostname')) {
            $hostname = gethostname();
        } else {
            $hostname = php_uname('n');
        }
        return $hostname;
    }

    function isOwned(Worker $worker){
        return $worker->getHostname() == $this->getHostname();
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

        $worker->setHostname($this->getHostname());

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

        $process = new Process(); // @todo When worker is on another host, Process is over kill.
        $process->setPid($pid);
        $worker->setProcess($process);

        foreach ($queues as $queue) {
            $worker->addQueue($this->queueFactory->createQueue($queue)); // @todo what about wildcard queues? :(
        }

        return $worker;
    }
}
