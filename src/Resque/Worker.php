<?php

namespace Resque;

use Predis\ClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Resque\Event\EventDispatcher;
use Resque\Event\EventDispatcherInterface;
use Resque\Event\JobAfterPerformEvent;
use Resque\Event\JobBeforePerformEvent;
use Resque\Event\JobFailedEvent;
use Resque\Event\JobPerformedEvent;
use Resque\Event\WorkerAfterForkEvent;
use Resque\Event\WorkerBeforeForkEvent;
use Resque\Event\WorkerStartupEvent;
use Resque\Failure\FailureInterface;
use Resque\Job\Exception\DirtyExitException;
use Resque\Job\Exception\InvalidJobException;
use Resque\Job\JobInstanceFactory;
use Resque\Job\JobInstanceFactoryInterface;
use Resque\Job\PerformantJobInterface;
use Resque\Job\JobInterface;
use Resque\Job\Status;

/**
 * Resque Worker
 *
 * The worker handles querying it issued queues for jobs, running them and handling the result.
 */
class Worker implements WorkerInterface
{
    /**
     * @var string String identifying this worker.
     */
    protected $id;

    /**
     * @var int Process id, used by Foreman.
     */
    protected $pid;

    /**
     * @var LoggerInterface Logging object that implements the PSR-3 LoggerInterface
     */
    protected $logger;

    /**
     * @var array Array of all associated queues for this worker.
     */
    protected $queues = array();

    /**
     * @var string The hostname of this worker.
     */
    protected $hostname;

    /**
     * @var boolean True if on the next iteration, the worker should shutdown.
     */
    protected $shutdown = false;

    /**
     * @var boolean True if this worker is paused.
     */
    protected $paused = false;

    /**
     * @var JobInterface Current job, if any, being processed by this worker.
     */
    protected $currentJob = null;

    /**
     * @var int Process ID of child worker processes.
     */
    protected $childPid = null;

    /**
     * @var Event\EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var Failure\FailureInterface
     */
    protected $failureBackend;

    /**
     * @var bool if the worker should fork to perform.
     */
    protected $fork = true;

    /**
     * @var ClientInterface Redis connection.
     */
    protected $redis;

    /**
     * Constructor
     *
     * Instantiate a new worker, given queues that it should be working on. The list of queues should be supplied in
     * the priority that they should be checked for jobs (first come, first served)
     *
     * @param QueueInterface|QueueInterface[] $queues A QueueInterface, or an array with multiple.
     * @param JobInstanceFactoryInterface|null $jobFactory
     * @param EventDispatcherInterface|null $eventDispatcher
     * @param LoggerInterface|null $logger A PSR-3 LoggerInterface object.
     */
    public function __construct(
        $queues = null,
        // Redis?
        JobInstanceFactoryInterface $jobFactory = null,
        // FailureBackend?
        EventDispatcherInterface $eventDispatcher = null,
        LoggerInterface $logger = null
    ) {
        $this->jobFactory = $jobFactory ?: new JobInstanceFactory();
        $this->eventDispatcher = $eventDispatcher ?: new EventDispatcher();
        $this->logger = $logger ?: new NullLogger();

        if (false === (null === $queues)) {
            if (!is_array($queues)) {
                $this->addQueue($queues);
            } else {
                $this->addQueues($queues);
            }
        }

        if (function_exists('gethostname')) {
            $this->hostname = gethostname();
        } else {
            $this->hostname = php_uname('n');
        }
    }

    /**
     * @param QueueInterface $queue
     * @return $this
     */
    public function addQueue(QueueInterface $queue)
    {
        $this->queues[(string)$queue] = $queue;

        return $this;
    }

    public function addQueues($queues)
    {
        foreach ($queues as $queue) {
            $this->addQueue($queue);
        }

        return $this;
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
     * @param FailureInterface $failureBackend
     * @return $this
     */
    public function setFailureBackend(FailureInterface $failureBackend)
    {
        $this->failureBackend = $failureBackend;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param int $pid
     * @return $this
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * @return QueueInterface[] Array of queues this worker is dealing with.
     */
    public function getQueues()
    {
        return $this->queues;
    }

    /**
     * Work
     *
     * The primary loop for a worker which when called on an instance starts
     * the worker's life cycle.
     *
     * Queues are checked every $interval (seconds) for new jobs.
     *
     * @param int $interval How often to check for new jobs across the queues. @todo remove, use setInterval or similar
     * @param bool $blocking @todo remove, use setBlocking or similar, but this should be on the Queue.
     */
    public function work($interval = 3, $blocking = false)
    {
        $this->startup();

        while (true) {
            if ($this->shutdown) {

                break;
            }

            if ($this->paused) {
                $this->updateProcTitle('Paused');
                usleep($interval * 1000000);

                continue;
            }

            $job = $this->reserve();

            // Attempt to find and reserve a job
            // @todo work out the worth of the block below
//            $job = false;
//            if (!$this->paused) {
//                if ($blocking === true) {
//                    $this->logger->log(
//                        LogLevel::INFO,
//                        'Starting blocking with timeout of {interval}',
//                        array('interval' => $interval)
//                    );
//                    $this->updateProcTitle(
//                        'Waiting for ' . implode(',', $this->queues) . ' with blocking timeout ' . $interval
//                    );
//                } else {
//                    $this->updateProcTitle('Waiting for ' . implode(',', $this->queues) . ' with interval ' . $interval);
//                }
//            }

            if (null === $job) {
                // For an interval of 0, break now - helps with unit testing etc
                // @todo replace with some method, which can be mocked... an interval of 0 should be considered valid
                if ($interval == 0) {

                    break;
                }

                if ($blocking === false) {
                    // If no job was found, we sleep for $interval before continuing and checking again
                    $this->logger->debug('Sleeping for {interval}', array('interval' => $interval));
                    if ($this->paused) {
                    } else {
                        $this->updateProcTitle('Waiting for ' . implode(',', $this->queues));
                    }

                    usleep($interval * 1000000);
                }

                continue;
            }

            $this->workingOn($job);

            if ($this->fork) {
                $this->eventDispatcher->dispatch(
                    new WorkerBeforeForkEvent($this, $job)
                );

                $this->redis->disconnect();

                $this->childPid = Foreman::fork();

                if (0 === $this->childPid) {
                    // Forked and we're the child
                    $this->eventDispatcher->dispatch(
                        new WorkerAfterForkEvent($this, $job)
                    );

                    $this->perform($job);

                    exit(0);
                }

                if ($this->childPid > 0) {
                    // Forked and we're the parent, sit and wait
                    $status = 'Forked ' . $this->childPid . ' at ' . strftime('%F %T');
                    $this->updateProcTitle($status);
                    $this->logger->debug($status);

                    // Wait until the child process finishes before continuing
                    pcntl_wait($waitStatus);
                    $exitStatus = pcntl_wexitstatus($waitStatus);

                    if ($exitStatus !== 0) {
                        $exception = new DirtyExitException(
                            'Job exited with exit code ' . $exitStatus
                        );

                        $this->handleFailedJob($job, $exception);
                    }
                }

                $this->childPid = null;
            } else {
                $this->perform($job);
            }

            $this->workComplete($job);
        }
    }

    /**
     * Perform necessary actions to start a worker.
     */
    protected function startup()
    {
        $this->updateProcTitle('Starting');
        $this->registerSigHandlers();

        $this->eventDispatcher->dispatch(
            new WorkerStartupEvent($this)
        );
    }

    /**
     * Set process name
     *
     * If possible, sets the name of the currently running process, to indicate the current state of the worker.
     *
     * Only supported systems with the PECL proctitle module installed, or CLI SAPI > 5.5.
     *
     * @see http://pecl.php.net/package/proctitle
     * @see http://php.net/manual/en/function.cli-set-process-title.php
     *
     * @param string $status The updated process title.
     */
    protected function updateProcTitle($status)
    {
        $processTitle = 'resque-' . Resque::VERSION . ': ' . $status;

        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($processTitle);

            return;
        }

        if (function_exists('setproctitle')) {
            setproctitle($processTitle);
        }
    }

    /**
     * Register signal handlers that a worker should respond to.
     *
     * TERM: Shutdown immediately and stop processing jobs.
     * INT: Shutdown immediately and stop processing jobs.
     * QUIT: Shutdown after the current job finishes processing.
     * USR1: Kill the forked child immediately and continue processing jobs.
     */
    private function registerSigHandlers()
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        declare(ticks = 1);
        pcntl_signal(SIGTERM, array($this, 'shutDownNow'));
        pcntl_signal(SIGINT, array($this, 'shutDownNow'));
        pcntl_signal(SIGQUIT, array($this, 'shutdown'));
        pcntl_signal(SIGUSR1, array($this, 'killChild'));
        pcntl_signal(SIGUSR2, array($this, 'pauseProcessing'));
        pcntl_signal(SIGCONT, array($this, 'resumeProcessing'));
        $this->logger->debug('Registered signals');
    }

    /**
     * @todo The name reserve doesn't sit well, all it's doing is asking queues for jobs. Change it.
     *
     * @return JobInterface|null Instance of JobInterface if a job is found, null if not.
     */
    public function reserve()
    {
        $queues = $this->queues();

        if (!is_array($queues)) {
            return null;
        }

        foreach ($queues as $queue) {
            $this->logger->debug('Checking {queue} for jobs', array('queue' => $queue));
            $job = $queue->pop();
            if (false === (null === $job)) {
                $this->logger->info('Found job on {queue}', array('queue' => $queue));

                return $job;
            }
        }

        return null;
    }

    /**
     * Return an array containing all of the queues that this worker should use
     * when searching for jobs.
     *
     * @return QueueInterface[] Array of associated queues.
     */
    public function queues()
    {
        return $this->queues;
    }

    /**
     * Tell Redis which job we're currently working on.
     *
     * @todo storage
     *
     * @param JobInterface $job The job we're working on.
     */
    public function workingOn(JobInterface $job)
    {
        $this->logger->notice(
            sprintf(
                'Starting work on %s',
                $job
            ),
            array(
                'job' => $job
            )
        );

        $this->currentJob = $job;

        $job->updateStatus(Status::STATUS_RUNNING);
        $data = json_encode(
            array(
                'queue' => $job->queue,
                'run_at' => strftime('%a %b %d %H:%M:%S %Z %Y'),
                'payload' => $job->jsonSerialize(),
            )
        );

        $this->redis->set('worker:' . $this, $data);
    }

    /**
     * Process a single job
     *
     * @throws InvalidJobException if the given job cannot actually be asked to perform.
     *
     * @param JobInterface $job The job to be processed.
     * @return bool If job performed or not.
     */
    public function perform(JobInterface $job)
    {
        $queue = null;

        $status = 'Performing Job ' . $job->getId();
        $this->updateProcTitle($status);
        $this->logger->info($status);

        try {
            $jobInstance = $this->jobFactory->createJob($job);

            if (false === ($jobInstance instanceof PerformantJobInterface)) {
                throw new InvalidJobException(
                    'Job ' . $job->getId(). ' "' . get_class($jobInstance) . '" needs to implement Resque\JobInterface'
                );
            }

            $this->eventDispatcher->dispatch(
                new JobBeforePerformEvent($job, $jobInstance)
            );

            $jobInstance->perform();

            $this->eventDispatcher->dispatch(
                new JobAfterPerformEvent($job, $jobInstance)
            );
        } catch (\Exception $exception) {

            $this->handleFailedJob($job, $exception);

            return false;
        }

        // $job->updateStatus(Status::STATUS_COMPLETE); @todo update status behaviour

        $this->logger->notice('{job} has successfully processed', array('job' => $job));

        $this->eventDispatcher->dispatch(
            new JobPerformedEvent($job)
        );

        return true;
    }

    protected function handleFailedJob(JobInterface $job, \Exception $exception)
    {
        $this->logger->error(
            'Perform failure on {job}, {message}',
            array(
                'job' => $job,
                'message' => $exception->getMessage()
            )
        );

        $this->failureBackend->save($job, $exception, $job->queue, $this);

        // $job->updateStatus(Status::STATUS_FAILED);
        // Stat::incr('failed');
        // Stat::incr('failed:' . $this->worker);

        $this->eventDispatcher->dispatch(
            new JobFailedEvent($job, $exception, null, $this)
        );
    }

    /**
     * Notify Redis that we've finished working on a job, clearing the working
     * state and incrementing the job stats.
     *
     * @todo storage
     */
    protected function workComplete()
    {
        $this->currentJob = null;
        Stat::incr('processed');
        Stat::incr('processed:' . $this->getId());
        $this->redis->del('worker:' . $this->getId());
    }

    /**
     * Worker ID
     *
     * @return string
     */
    public function getId()
    {
        if (null === $this->id) {
            $this->id = $this->hostname . ':' . getmypid() . ':' . implode(',', $this->queues);
        }

        return $this->id;
    }

    /**
     * Set the ID of this worker to a given ID string.
     *
     * @param string $id ID for the worker.
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Signal handler callback for USR2, pauses processing of new jobs.
     */
    public function pauseProcessing()
    {
        $this->logger->notice('USR2 received; pausing job processing');
        $this->paused = true;
    }

    /**
     * Signal handler callback for CONT, resumes worker allowing it to pick
     * up new jobs.
     */
    public function resumeProcessing()
    {
        $this->logger->notice('CONT received; resuming job processing');
        $this->paused = false;
    }

    /**
     * Force an immediate shutdown of the worker, killing any child jobs
     * currently running.
     */
    public function shutdownNow()
    {
        $this->shutdown();
        $this->killChild();
    }

    /**
     * Schedule a worker for shutdown. Will finish processing the current job
     * and when the timeout interval is reached, the worker will shut down.
     */
    public function shutdown()
    {
        $this->shutdown = true;
        $this->logger->notice('Shutting down');
    }

    /**
     * Kill a forked child job immediately. The job it is processing will not
     * be completed.
     */
    public function killChild()
    {
        if (!$this->childPid) {
            $this->logger->debug('No child to kill.');
            return;
        }

        $this->logger->debug(
            'Killing child at {child}',
            array('child' => $this->childPid)
        );

        if (exec('ps -o pid,state -p ' . $this->childPid, $output, $returnCode) && $returnCode != 1) {
            $this->logger->debug(
                'Child {child} found, killing.',
                array('child' => $this->childPid)
            );
            posix_kill($this->childPid, SIGKILL);
            $this->childPid = null;
        } else {
            $this->logger->warning(
                'Child {child} not found, restarting.',
                array('child' => $this->childPid)
            );

            $this->shutdown();
        }
    }

    /**
     * Generate a string representation of this worker.
     *
     * @return string String identifier for this worker instance.
     */
    public function __toString()
    {
        return $this->getId();
    }

    /**
     * Return the Job this worker is currently working on.
     *
     * @return Job|null The current Job this worker is processing, null if it is not processing a job currently.
     */
    public function getCurrentJob()
    {
        return $this->currentJob;
    }

    /**
     * Get a statistic belonging to this worker.
     *
     * @param string $stat Statistic to fetch.
     * @return int Statistic value.
     */
    public function getStat($stat)
    {
        return Stat::get($stat . ':' . $this);
    }

    /**
     * Inject the logging object into the worker
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * "Will fork for food"
     *
     * @param bool $fork If the worker should fork to do work
     */
    public function setForkOnPerform($fork)
    {
        $this->fork = (bool)$fork;
    }
}
