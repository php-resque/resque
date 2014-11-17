<?php

namespace Resque\Component\Worker;

use Predis\ClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Resque\Component\Core\Event\EventDispatcher;
use Resque\Component\Core\Exception\ResqueRuntimeException;
use Resque\Component\Core\Process;
use Resque\Component\Job\Event\JobEvent;
use Resque\Component\Job\Event\JobFailedEvent;
use Resque\Component\Job\Exception\DirtyExitException;
use Resque\Component\Job\Exception\InvalidJobException;
use Resque\Component\Job\Factory\JobInstanceFactory;
use Resque\Component\Job\Factory\JobInstanceFactoryInterface;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\Model\TrackableJobInterface;
use Resque\Component\Job\PerformantJobInterface;
use Resque\Component\Job\ResqueJobEvents;
use Resque\Component\Queue\Model\OriginQueueAwareInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Worker\Event\WorkerEvent;
use Resque\Component\Worker\Event\WorkerJobEvent;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Event\EventDispatcherInterface;
use Resque\Event\JobAfterPerformEvent;
use Resque\Event\JobBeforePerformEvent;
use Resque\Event\WorkerAfterForkEvent;
use Resque\Event\WorkerBeforeForkEvent;
use Resque\Failure;
use Resque\Failure\BlackHoleFailure;
use Resque\Failure\FailureInterface;
use Resque\Job\Status;
use Resque\Statistic\BlackHoleStatistic;
use Resque\Statistic\StatisticInterface;

/**
 * Resque Worker
 *
 * The worker handles querying it issued queues for jobs, running them and handling the result.
 */
class Worker implements WorkerInterface, LoggerAwareInterface
{
    /**
     * @var string String identifying this worker.
     */
    protected $id;

    /**
     * @var Process The workers current process.
     */
    protected $process;

    /**
     * @var Process|null The workers child process, if it currently has one.
     */
    protected $childProcess = null;

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
     * @var \Resque\Component\Job\Model\JobInterface Current job being processed by this worker.
     */
    protected $currentJob = null;

    /**
     * @var \Resque\Component\Core\Event\EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var Failure\FailureInterface
     */
    protected $failureBackend;

    /**
     * @var StatisticInterface
     */
    protected $statisticsBackend;

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
     * @param \Resque\Component\Job\Factory\JobInstanceFactoryInterface|null $jobFactory
     * @param EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct(
        $queues = null,
        // Redis?
        JobInstanceFactoryInterface $jobFactory = null,
        // FailureBackend?
        EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->jobFactory = $jobFactory ?: new JobInstanceFactory();
        $this->eventDispatcher = $eventDispatcher ?: new EventDispatcher();

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
        $this->queues[$queue->getName()] = $queue;

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
     * Return an array containing all of the queues that this worker should use when searching for jobs.
     *
     * @return QueueInterface[] Array of queues this worker is dealing with.
     */
    public function queues()
    {
        return $this->queues;
    }

    /**
     * @param ClientInterface $redis
     * @return $this
     */
    public function setRedisClient(ClientInterface $redis)
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
     * @return FailureInterface
     */
    public function getFailureBackend()
    {
        if (null === $this->failureBackend) {
            $this->setFailureBackend(new BlackHoleFailure());
        }

        return $this->failureBackend;
    }

    /**
     * Set statistic backend
     *
     * @param StatisticInterface $statisticsBackend
     * @return $this
     */
    public function setStatisticsBackend(StatisticInterface $statisticsBackend)
    {
        $this->statisticsBackend = $statisticsBackend;

        return $this;
    }

    /**
     * @return StatisticInterface
     */
    public function getStatisticsBackend()
    {
        if (null === $this->statisticsBackend) {
            $this->setStatisticsBackend(new BlackHoleStatistic());
        }

        return $this->statisticsBackend;
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

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            $this->setLogger(new NullLogger());
        }

        return $this->logger;
    }

    /**
     * @param Process $process
     * @return $this
     */
    public function setProcess(Process $process)
    {
        $this->process = $process;

        return $this;
    }

    /**
     * @return Process
     */
    public function getProcess()
    {
        if (null === $this->process) {
            $process = new Process();
            $process->setPidFromCurrentProcess();
            $this->setProcess($process);
        }

        return $this->process;
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
     */
    public function work($interval = 3)
    {
        $this->startup();

        while (true) {
            if ($this->shutdown) {

                break;
            }

            $this->getProcess()->dispatchSignals();

            if ($this->paused) {
                $this->getProcess()->setTitle('Paused');
                usleep($interval * 1000000);

                if ($interval == 0) {

                    break;
                }

                continue;
            }

            $job = $this->reserve();

            if (null === $job) {
                // For an interval of 0, break now - helps with unit testing etc
                // @todo replace with some method, which can be mocked... an interval of 0 should be considered valid
                if ($interval == 0) {

                    break;
                }

                continue;
            }

            $this->workingOn($job);

            if ($this->fork) {
                $this->eventDispatcher->dispatch(
                    ResqueWorkerEvents::BEFORE_FORK_TO_PERFORM,
                    new WorkerJobEvent($this, $job)
                );

                $this->redis->disconnect();

                $this->childProcess = $this->getProcess()->fork();

                if (null === $this->childProcess) {
                    // This is child process, it will perform the job and then die.
                    $this->eventDispatcher->dispatch(
                        ResqueWorkerEvents::AFTER_FORK_TO_PERFORM,
                        new WorkerJobEvent($this, $job)
                    );

                    $this->perform($job);

                    exit(0);
                } else {
                    // We're the parent, sit and wait.

                    $title = 'Forked ' . $this->childProcess->getPid() . ' at ' . date('c');
                    $this->getProcess()->setTitle($title);
                    $this->getLogger()->debug($title);

                    // Wait until the child process finishes before continuing
                    $this->childProcess->wait();

                    if (false === $this->childProcess->isCleanExit()) {
                        $exception = new DirtyExitException(
                            'Job exited with exit code ' . $this->childProcess->getExitCode()
                        );

                        $this->handleFailedJob($job, $exception);
                    }
                }

                // Child should be dead now.
                $this->childProcess = null;
            } else {
                $this->perform($job);
            }

            $this->workComplete($job);
        }
    }

    /**
     * Perform necessary actions to start a worker
     */
    protected function startup()
    {
        $this->getProcess()->setPidFromCurrentProcess();
        $this->getProcess()->setTitle('Starting');
        $this->registerSignalHandlers();

        $this->eventDispatcher->dispatch(
            ResqueWorkerEvents::START_UP,
            new WorkerEvent($this)
        );
    }

    /**
     * @todo The name reserve doesn't sit well, all it's doing is asking queues for jobs. Change it.
     *
     * @return \Resque\Component\Job\Model\JobInterface|null Instance of JobInterface if a job is found, null if not.
     */
    public function reserve()
    {
        $queues = $this->queues();

        foreach ($queues as $queue) {
            $this->getLogger()->debug('Checking {queue} for jobs', array('queue' => $queue));
            $job = $queue->pop();
            if (false === (null === $job)) {
                $this->getLogger()->info('Found job on {queue}', array('queue' => $queue));

                return $job;
            }
        }

        return null;
    }

    /**
     * Tell Redis which job we're currently working on.
     *
     * @param \Resque\Component\Job\Model\JobInterface $job The job we're working on.
     */
    public function workingOn(JobInterface $job)
    {
        $this->getLogger()->notice('Starting work on {job}', array('job' => $job));

        if ($job instanceof TrackableJobInterface) {
            $job->setState(JobInterface::STATE_PERFORMING);
        }

        $this->setCurrentJob($job);
    }

    /**
     * Process a single job
     *
     * @throws InvalidJobException if the given job cannot actually be asked to perform.
     *
     * @param \Resque\Component\Job\Model\JobInterface $job The job to be processed.
     * @return bool If job performed or not.
     */
    public function perform(JobInterface $job)
    {
        $status = 'Performing job ' . $job->getId();
        $this->getProcess()->setTitle($status);
        $this->getLogger()->info($status);

        try {
            $jobInstance = $this->jobFactory->createJob($job);

            if (false === ($jobInstance instanceof PerformantJobInterface)) {
                throw new InvalidJobException(
                    'Job ' . $job->getId(). ' "' . get_class($jobInstance) . '" needs to implement Resque\JobInterface'
                );
            }

            $this->eventDispatcher->dispatch(
                ResqueJobEvents::BEFORE_PERFORM,
                new JobBeforePerformEvent($job, $jobInstance)
            );

            $jobInstance->perform($job->getArguments());

        } catch (\Exception $exception) {

            $this->handleFailedJob($job, $exception);

            return false;
        }

        // $job->updateStatus(Status::STATUS_COMPLETE); @todo update status behaviour

        $this->getLogger()->notice('{job} has successfully processed', array('job' => $job));

        $this->eventDispatcher->dispatch(ResqueJobEvents::PERFORMED, new JobEvent($job));

        return true;
    }

    /**
     * Handle failed job
     *
     * @param \Resque\Component\Job\Model\JobInterface $job The job that failed.
     * @param \Exception $exception The reason the job failed.
     */
    protected function handleFailedJob(JobInterface $job, \Exception $exception)
    {
        $this->getLogger()->error(
            'Perform failure on {job}, {message}',
            array(
                'job' => $job,
                'message' => $exception->getMessage()
            )
        );

        $this->getFailureBackend()->save($job, $exception, $this);

        // $job->updateStatus(Status::STATUS_FAILED);
        $this->getStatisticsBackend()->increment('failed');
        $this->getStatisticsBackend()->increment('failed:' . $this->getId());

        $this->eventDispatcher->dispatch(
            ResqueJobEvents::FAILED,
            new JobFailedEvent($job, $exception, $this)
        );
    }

    /**
     * Notify Redis that we've finished working on a job, clearing the working
     * state and incrementing the job stats.
     *
     * @param \Resque\Component\Job\Model\JobInterface $job
     */
    protected function workComplete(JobInterface $job)
    {
        $this->getStatisticsBackend()->increment('processed');
        $this->getStatisticsBackend()->increment('processed:' . $this->getId());
        $this->setCurrentJob(null);
        $this->getLogger()->debug('Work complete on {job}', array('job' => $job));
    }

    /**
     * Worker ID
     *
     * @return string
     */
    public function getId()
    {
        if (null === $this->id) {
            $this->setId($this->hostname . ':' . getmypid() . ':' . implode(',', $this->queues()));
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
     * Register signal handlers that a worker should respond to.
     *
     * TERM: Shutdown immediately and stop processing jobs.
     * INT: Shutdown immediately and stop processing jobs.
     * QUIT: Shutdown after the current job finishes processing.
     * USR1: Kill the forked child immediately and continue processing jobs.
     */
    private function registerSignalHandlers()
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        declare(ticks = 100);

        pcntl_signal(SIGTERM, array($this, 'shutDownNow'));
        pcntl_signal(SIGINT, array($this, 'shutDownNow'));
        pcntl_signal(SIGQUIT, array($this, 'shutdown'));
        pcntl_signal(SIGUSR1, array($this, 'killChild'));
        pcntl_signal(SIGUSR2, array($this, 'pauseProcessing'));
        pcntl_signal(SIGCONT, array($this, 'resumeProcessing'));
        $this->getLogger()->debug('Registered signals');
    }

    /**
     * Signal handler callback for USR2, pauses processing of new jobs.
     */
    public function pauseProcessing()
    {
        $this->getLogger()->notice('SIGUSR2 received; pausing job processing');
        $this->paused = true;
    }

    /**
     * Signal handler callback for CONT, resumes worker allowing it to pick
     * up new jobs.
     */
    public function resumeProcessing()
    {
        $this->getLogger()->notice('SIGCONT received; resuming job processing');
        $this->paused = false;
    }

    /**
     * Force an immediate shutdown of the worker, killing any child jobs
     * currently running, or the job it is currently working on.
     */
    public function shutdownNow()
    {
        $this->shutdown();
        $this->killChild();

        $currentJob = $this->getCurrentJob();
        if (null !== $currentJob) {
            $this->handleFailedJob(
                $currentJob,
                new DirtyExitException('Worker forced shutdown killed job ' . $currentJob->getId())
            );
        }
    }

    /**
     * Schedule a worker for shutdown. Will finish processing the current job
     * and when the timeout interval is reached, the worker will shut down.
     */
    public function shutdown()
    {
        $this->shutdown = true;
        $this->getLogger()->notice('Worker {worker} shutting down', array('worker' => $this));
    }

    /**
     * Kill a forked child job immediately. The job it is processing will not
     * be completed.
     */
    public function killChild()
    {
        if (null === $this->childProcess || !$this->childProcess->getPid()) {
            $this->getLogger()->debug('No child to kill for worker {worker}', array('worker' => $this));

            return;
        }

        $this->childProcess->kill();
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
     * Set current job
     *
     * Sets which job the worker is currently working on, and records it in redis.
     *
     * @param \Resque\Component\Job\Model\JobInterface|null $job The job being worked on, or null if the worker isn't processing a job anymore.
     * @throws ResqueRuntimeException when current job is not cleared before setting a different one.
     */
    public function setCurrentJob(JobInterface $job = null)
    {
        if (null !== $job && null !== $this->getCurrentJob()) {
            throw new ResqueRuntimeException(
                sprintf(
                    'Cannot set current job to %s when current job is not null, current job is %s',
                    $job,
                    $this->getCurrentJob()
                )
            );
        }

        $this->currentJob = $job;

        if (null === $this->getCurrentJob()) {
            $this->redis->del('worker:' . $this->getId());

            return;
        }

        $payload = json_encode(
            array(
                'queue' => ($job instanceof OriginQueueAwareInterface) ? $job->getOriginQueue() : null,
                'run_at' => date('c'),
                'payload' => $job::encode($job),
            )
        );

        $this->redis->set('worker:' . $this->getId(), $payload);
    }

    /**
     * Return the Job this worker is currently working on.
     *
     * @return \Resque\Component\Job\Model\JobInterface|null The current Job this worker is processing, null if currently not processing a job
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
        return $this->getStatisticsBackend()->get($stat . ':' . $this->getId());
    }

    /**
     * Clear worker stats
     */
    public function clearStats()
    {
        $this->getStatisticsBackend()->clear('processed:' . $this->getId());
        $this->getStatisticsBackend()->clear('failed:' . $this->getId());
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

    /**
     * Set hostname
     *
     * @param string $hostname The name of the host the worker is/was running on.
     * @return $this
     */
    public function setHostname($hostname)
    {
        // TODO: Implement setHostname() method.
    }

    /**
     * Get hostname
     *
     * @return string $hostname The name of the host the worker is/was running on.
     */
    public function getHostname()
    {
        // TODO: Implement getHostname() method.
    }
}
