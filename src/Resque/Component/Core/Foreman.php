<?php

namespace Resque\Component\Core;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Resque\Component\Core\Exception\ResqueRuntimeException;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\Registry\WorkerRegistryInterface;
use Resque\Component\Worker\Worker;
use Resque\Redis\RedisClientInterface;

/**
 * Resque Foreman
 *
 * Handles pruning, forking, killing and general management of workers.
 */
class Foreman implements LoggerAwareInterface
{
    /**
     * @var string The hostname of the current machine.
     */
    protected $hostname;

    /**
     * @var WorkerRegistryInterface A worker registry.
     */
    protected $registry;

    /**
     * @var LoggerInterface Logging object that implements the PSR-3 LoggerInterface
     */
    protected $logger;

    /**
     * @var bool If the foreman was last called to work(), halt() will reset when implemented
     */
    protected $working;

    protected $redis;

    public function __construct(WorkerRegistryInterface $workerRegistry, RedisClientInterface $redis)
    {
        $this->logger = new NullLogger();
        $this->registry = $workerRegistry;
        $this->redis = $redis;

        if (function_exists('gethostname')) {
            $this->hostname = gethostname();
        } else {
            $this->hostname = php_uname('n');
        }
    }

    /**
     * Inject a logging object into the worker
     *
     * @param LoggerInterface $logger
     * @return null|void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function startWorker(Worker $worker){
        $parent = new Process();

        $this->redis->disconnect();
        $child = $parent->fork();

        if (null === $child) {
            // This is worker process, it will process jobs until told to exit.
            $this->registry->register($worker);
            $worker->work();
            $this->registry->deregister($worker);

            exit(0);
        }

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
     * Work
     *
     * Given workers this will fork a new process for each worker and set them to work, whilst registering them with
     * the worker registry.
     *
     * @param WorkerInterface[] $workers An array of workers you would like forked into child processes and set
     *                          on their way.
     * @param bool $wait If true, this Foreman will wait for the workers to complete. This will guarantee workers are
     *                   cleaned up after correctly, however this is not really practical for most purposes.
     */
    public function work($workers, $wait = false)
    {
        if($this->working){
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
            $this->wait($workers);
        }
    }

    public function wait($workers){
        foreach ($workers as $worker) {
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
    }

    /**
     * Prune dead workers
     *
     * Look for any workers which should be running on this server and if
     * they're not, remove them from Redis.
     *
     * This is a form of garbage collection to handle cases where the
     * server may have been killed and the Resque workers did not die gracefully
     * and therefore leave state information in Redis.
     */
    public function pruneDeadWorkers()
    {
        $workerPids = $this->getLocalWorkerPids();
        $workers = $this->registry->all(); // @todo Maybe findWorkersForHost($hostname) ?
        foreach ($workers as $worker) {
            if ($worker instanceof WorkerInterface) {
                $pid = $worker->getProcess()->getPid();
                if ($worker->getHostname() != $this->hostname || in_array($pid, $workerPids) || $pid == getmypid()) {

                    continue;
                }
                $this->logger->warning('Pruning dead worker {worker}', array('worker' => $worker));
                $this->registry->deregister($worker);
            }
        }
    }

    /**
     * Local worker process IDs
     *
     * Return an array of process IDs for all of the Resque workers currently running on this machine.
     *
     * @return array An array of Resque worker process IDs.
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
