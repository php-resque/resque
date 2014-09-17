<?php

namespace Resque;

use Predis\ClientInterface;
use Psr\Log\NullLogger;
use Resque\Exception\ResqueRuntimeException;

/**
 * Resque Foreman
 *
 * Handles creating, pruning, forking, killing and general management of workers.
 */
class Foreman
{
    /**
     * @var array
     */
    protected $workers;

    /**
     * @var array Workers currently registered in Redis as work() has been called.
     */
    protected $registeredWorkers;

    /**
     * @var ClientInterface Redis connection.
     */
    protected $redis;

    public function __construct()
    {
        $this->workers = array();
        $this->registeredWorkers = array();
        $this->logger = new NullLogger();

        if (function_exists('gethostname')) {
            $this->hostname = gethostname();
        } else {
            $this->hostname = php_uname('n');
        }
    }

    /**
     * @param ClientInterface $redis
     * @return $this
     */
    public function setRedisBackend(ClientInterface $redis)
    {
        $this->redis = $redis;

        return $this;
    }

    /**
     * Given a worker ID, find it and return an instantiated worker class for it.
     *
     * @param string $workerId The ID of the worker.
     * @return Worker Instance of the worker. null if the worker does not exist.
     */
    public function findWorkerById($workerId)
    {
        if (false /** === $this->exists($workerId) */ || false === strpos($workerId, ":")) {

            return null;
        }

        list($hostname, $pid, $queues) = explode(':', $workerId, 3);
        $queues = explode(',', $queues);

        $worker = new Worker();
        foreach ($queues as $queue) {
            $worker->addQueue(new Queue($queue));
        }

        $worker->setId($workerId);

        return $worker;
    }

    /**
     * Return all workers known to Resque as instantiated instances.
     * @return Worker[]
     */
    public function all()
    {
        $workers = $this->redis->smembers('workers');

        if (!is_array($workers)) {
            $workers = array();
        }

        $instances = array();
        foreach ($workers as $workerId) {
            $instances[] = $this->findWorkerById($workerId);
        }

        return $instances;
    }

    /**
     * Registers the given worker in Redis.
     *
     * @throws \Exception
     *
     * @param Worker $worker
     * @return $this
     */
    public function registerWorker(Worker $worker)
    {
        if (in_array($worker, $this->registeredWorkers, true)) {
            throw new \Exception('Cannot double register a worker, call unregister(), or halt() to clear');
        }

        $id = $worker->getId();

        $this->registeredWorkers[$id] = $worker;

        $this->redis->sadd('workers', $id);
        $this->redis->set('worker:' . $id . ':started', strftime('%a %b %d %H:%M:%S %Z %Y'));

        return $this;
    }

    /**
     * Unregisters the given worker from Redis.
     */
    public function unregisterWorker(Worker $worker)
    {
        $id = $worker->getId();
        // @todo Restore.
//        if(is_object($this->currentJob)) {
//            $this->currentJob->fail(new Resque_Job_DirtyExitException);
//        }

        $this->redis->srem('workers', $id);
        $this->redis->del('worker:' . $id);
        $this->redis->del('worker:' . $id . ':started');

//        Stat::clear('processed:' . $id);
//        Stat::clear('failed:' . $id);

        unset($this->registeredWorkers[$id]);
    }

    /**
     * Given a worker ID, check if it is registered/valid.
     *
     * @param Worker $worker The worker.
     * @return boolean True if the worker exists in redis, false if not.
     */
    public function isRegistered(Worker $worker)
    {
        return (bool)$this->redis->sismember('workers', (string)$worker);
    }

    public function work($workers)
    {
        // @todo Guard multiple calls. Expect ->work() ->halt() ->work() etc
        // @todo Check workers are instanceof Worker.

        $this->redis->disconnect();

        /** @var Worker $worker */
        foreach ($workers as $worker) {
            $worker->setPid(self::fork());
            if (!$worker->getPid()) {
                // This is child process, it will work and then die.
                $this->registerWorker($worker);
                $worker->work(0);
                $this->unregisterWorker($worker);

                exit();
            }
        }

        // wait for slaves
        foreach ($workers as $worker) {
            $status = 0;
            if ($worker->getPid() != pcntl_waitpid($worker->getPid(), $status)) {
                die("Error with wait pid $worker->getPid().\n");
            } else {
                $this->unregisterWorker($worker);
            }
        }
    }

    /**
     * fork() helper method for php-resque
     *
     * @see pcntl_fork()
     *
     * @return int Return vars as per pcntl_fork()
     * @throws ResqueRuntimeException when fork failed.
     */
    public static function fork()
    {
        if (!function_exists('pcntl_fork')) {
            // @todo work out if this should throw an exception as -1 does below. Not having pcntl is just as bad as
            //       it is failing.
            throw new ResqueRuntimeException('pcntl_fork is not available');
        }

        $pid = pcntl_fork();

        if ($pid === -1) {
            // @todo better message, by using pcntl_get_last_error() then pcntl_strerror()
            throw new ResqueRuntimeException('Unable to fork child worker.');
        }

        return $pid;
    }

    /**
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
        $workers = $this->all();
        foreach ($workers as $worker) {
            if (is_object($worker)) {
                list($host, $pid, $queues) = explode(':', (string)$worker, 3);
                if ($host != $this->hostname || in_array($pid, $workerPids) || $pid == getmypid()) {

                    continue;
                }
                $this->logger->warning('Pruning dead worker {worker}', array('worker' => (string)$worker));
                $this->unregisterWorker($worker);
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
