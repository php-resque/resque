<?php

namespace Resque;

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
     * @var array
     */
    protected $registeredWorkers;

    public function __construct()
    {
        $this->workers = array();
        $this->registeredWorkers = array();
    }

    /**
     * Given a worker ID, find it and return an instantiated worker class for it.
     *
     * @param string $workerId The ID of the worker.
     * @return Worker Instance of the worker. False if the worker does not exist.
     */
    public function find($workerId)
    {
        if (false /** === $this->exists($workerId) */ || false === strpos($workerId, ":")) {
            return false;
        }

        list($hostname, $pid, $queues) = explode(':', $workerId, 3);
        $queues = explode(',', $queues);

        // @todo turn $queues in Queue objects.

        $worker = new Worker($queues);
        $worker->setId($workerId);
        return $worker;
    }

    /**
     * Return all workers known to Resque as instantiated instances.
     * @return Worker[]
     */
    public function all()
    {
        $workers = Resque::redis()->smembers('workers');

        if (!is_array($workers)) {
            $workers = array();
        }

        $instances = array();
        foreach ($workers as $workerId) {
            $instances[] = $this->find($workerId);
        }

        return $instances;
    }

    public function allLocal()
    {
        return $this->workers;
    }

    /**
     * @param Worker $worker
     * @return $this
     * @throws \Exception if the worker has already been added
     */
    public function addWorker(Worker $worker)
    {
        if (in_array($worker, $this->workers, true)) {
            throw new \Exception('Cannot add a worker that already exists');
        }

        $this->workers[] = $worker;

        return $this;
    }

    /**
     * Registers this worker in Redis.
     *
     * @param Worker $worker
     * @return $this
     */
    public function registerWorker(Worker $worker)
    {
        $this->registeredWorkers[(string) $worker] = $worker;

        Resque::redis()->sadd('workers', $worker);
        Resque::redis()->set('worker:' . $worker . ':started', strftime('%a %b %d %H:%M:%S %Z %Y'));

        return $this;
    }

    /**
     * Unregisters this worker in Redis. (shutdown etc)
     */
    public function unregisterWorker(Worker $worker)
    {
        // @todo Restore.
//        if(is_object($this->currentJob)) {
//            $this->currentJob->fail(new Resque_Job_DirtyExitException);
//        }

        $id = $worker->getId();
        Resque::redis()->srem('workers', $id);
        Resque::redis()->del('worker:' . $id);
        Resque::redis()->del('worker:' . $id . ':started');
        Stat::clear('processed:' . $id);
        Stat::clear('failed:' . $id);

        unset($this->registeredWorkers[(string) $worker]);
    }

    /**
     * Given a worker ID, check if it is registered/valid.
     *
     * @param Worker $worker The worker.
     * @return boolean True if the worker exists in redis, false if not.
     */
    public function isRegistered(Worker $worker)
    {
        return (bool) Resque::redis()->sismember('workers', (string) $worker);
    }

    public function work()
    {
        // @todo Guard multiple calls. Expect ->work() ->halt() ->work() etc

        foreach ($this->workers as $worker) {

            $worker->setPid(static::fork());

            if (!$worker->pid) {
                // This is child process, it will work and then die.
                $this->registerWorker($worker);
                $worker->work();
                $this->unregisterWorker($worker);

                exit();
            }
        }

        // wait for slaves
        foreach ($this->registeredWorkers as $worker) {
            $status = 0;
            if ($worker->pid != pcntl_waitpid($worker->pid, $status)) {
                die("Error with wait pid $worker->pid.\n");
            } else {
                $this->unregisterWorker($worker);
            }
        }
    }

    /**
     * fork() helper method for php-resque that handles issues PHP socket
     * and phpredis have with passing around sockets between child/parent
     * processes.
     *
     * Will close connection to Redis before forking.
     *
     * This will probably not be static.
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

        // Close the connection to Redis before forking.
        // This is a workaround for issues phpredis has.
        //self::$redis = null;
        // @todo change to this $this->backend->disconnect();, which means making non static.
        // Maybe throw a pre fork event?

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
        foreach($workers as $worker) {
            if (is_object($worker)) {
                list($host, $pid, $queues) = explode(':', (string)$worker, 3);
                if($host != $this->hostname || in_array($pid, $workerPids) || $pid == getmypid()) {
                    continue;
                }
                $this->logger->log(LogLevel::INFO, 'Pruning dead worker: {worker}', array('worker' => (string)$worker));
                $this->unregister($worker);
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
