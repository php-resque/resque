<?php

namespace Resque\Component\Core;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Core\Exception\ResqueRuntimeException;
use Resque\Component\System\SystemInterface;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\Registry\WorkerRegistryInterface;

/**
 * Resque Foreman
 *
 * Handles pruning, forking, killing and general management of workers.
 */
class Foreman implements LoggerAwareInterface
{
    /**
     * @var WorkerRegistryInterface A worker registry.
     */
    protected $registry;

    /**
     * @var EventDispatcherInterface An event dispatcher.
     */
    protected $eventDispatcher;

    /**
     * @var LoggerInterface Logging object that implements the PSR-3 LoggerInterface
     */
    protected $logger;

    /**
     * @var bool If the foreman was last called to work(), halt() will reset when implemented
     */
    protected $working;

    /**
     * @var SystemInterface
     */
    protected $system;

    /**
     * Constructor.
     *
     * @param WorkerRegistryInterface $workerRegistry
     * @param EventDispatcherInterface $eventDispatcher
     * @param SystemInterface $system
     */
    public function __construct(
        WorkerRegistryInterface $workerRegistry,
        EventDispatcherInterface $eventDispatcher,
        SystemInterface $system
    )
    {
        $this->logger = new NullLogger();
        $this->registry = $workerRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->system = $system;
    }

    /**
     * Set PSR-3 logger.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Work.
     *
     * Given workers this will fork a new process for each worker and set them to work, whilst registering them with
     * the worker registry.
     *
     * @param WorkerInterface[] $workers An array of workers you would like forked into child processes and set
     *                          on their way.
     * @param bool $wait If true, this Foreman will wait for the workers to complete. This will guarantee workers are
     *                   cleaned up after correctly, however this is not really practical for most purposes.
     * @return void
     */
    public function work($workers, $wait = false)
    {
        if ($this->working) {
            throw new ResqueRuntimeException(
                'Foreman error was last called to work, must be halted first'
            );
        }

        $this->working = true;

        // @todo Check workers are instanceof WorkerInterface.

        /** @var WorkerInterface $worker */
        foreach ($workers as $worker) {
            $this->startWorker($worker);
        }

        if ($wait) {
            foreach ($workers as $worker) {
                $this->deregisterOnWorkerExit($worker);
            }
        }
    }

    /**
     * Start worker.
     *
     * @param WorkerInterface $worker The worker to start.
     * @return void
     */
    public function startWorker(WorkerInterface $worker)
    {
        $parent = $this->system->createCurrentProcess();

        $this->eventDispatcher->dispatch(ResqueEvents::PRE_FORK);

        $child = $parent->fork();

        // This exists because workers that get reset after a crash still hold their old id.
        // @todo this shouldn't be needed if id was always derived.. hmm.
        $worker->setId(null);

        if (null === $child) {
            // This is spawned worker process, it will process jobs until told to exit.
            $this->eventDispatcher->dispatch(ResqueEvents::POST_FORK_CHILD);

            $worker->setProcess($this->system->createCurrentProcess());

            $this->registry->register($worker);
            $worker->work();
            $this->registry->deregister($worker);

            exit(0);
        }

        $this->eventDispatcher->dispatch(ResqueEvents::POST_FORK_PARENT);

        $worker->setProcess($child);

        $this->logger->info(
            'Successfully started worker {worker} with pid {childPid}',
            array(
                'worker' => $worker,
                'childPid' => $child->getPid(),
            )
        );
    }

    /**
     * Deregister on worker exit.
     *
     * @param WorkerInterface $worker The worker to wait for exit and then deregister.
     * @throws ResqueRuntimeException when $worker fails to exit cleanly.
     * @return void
     */
    public function deregisterOnWorkerExit(WorkerInterface $worker)
    {
        $process = $worker->getProcess();
        $process->wait();
        if ($process->isCleanExit()) {
            $this->registry->deregister($worker);
        } else {
            throw new ResqueRuntimeException(
                sprintf(
                    'Foreman error with worker %s wait on pid %d',
                    $worker->getId(),
                    $process->getPid()
                )
            );
        }
    }

    /**
     * Prune dead workers.
     *
     * Look for any workers which should be running on this server and if
     * they're not, remove them from Redis.
     *
     * This is a form of garbage collection to handle cases where the
     * server may have been killed and the workers did not exit gracefully
     * and therefore leave state information in Redis.
     *
     * @return void
     */
    public function pruneDeadWorkers()
    {
        $workerPids = $this->getLocalWorkerPids();
        $workers = $this->registry->all(); // @todo Maybe findWorkersForHost($hostname) ?
        $hostname = $this->system->getHostname();
        foreach ($workers as $worker) {
            if ($worker instanceof WorkerInterface) {
                $isNotOnCurrentHost = $worker->getHostname() != $hostname;
                $isCurrentlyRunning = in_array($worker->getProcess()->getPid(), $workerPids);
                $isCurrentProcess = $worker->getProcess()->getPid() == $this->system->getCurrentPid();

                if ($isNotOnCurrentHost || $isCurrentlyRunning || $isCurrentProcess) {
                    continue;
                }

                $this->logger->warning('Pruning dead worker {worker}', array('worker' => $worker));
                $this->registry->deregister($worker);
            }
        }
    }

    /**
     * Get local worker process IDs.
     *
     * Return an array of process IDs for all of the workers currently running on this machine.
     *
     * @return array An array of worker process IDs.
     */
    public function getLocalWorkerPids()
    {
        $pids = array();
        exec('ps -A -o pid,command | grep [r]esque', $cmdOutput); // @todo The hard coded [r]esque is dangerous.
        foreach ($cmdOutput as $line) {
            list($pids[],) = explode(' ', trim($line), 2);
        }

        return $pids;
    }
}
