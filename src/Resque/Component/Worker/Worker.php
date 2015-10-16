<?php

namespace Resque\Component\Worker;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Core\Exception\ResqueRuntimeException;
use Resque\Component\Core\Process;
use Resque\Component\Job\Event\JobFailedEvent;
use Resque\Component\Job\Event\JobInstanceEvent;
use Resque\Component\Job\Exception\DirtyExitException;
use Resque\Component\Job\Exception\InvalidJobException;
use Resque\Component\Job\Factory\JobInstanceFactoryInterface;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\Model\TrackableJobInterface;
use Resque\Component\Job\PerformantJobInterface;
use Resque\Component\Job\ResqueJobEvents;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Worker\Event\WorkerEvent;
use Resque\Component\Worker\Event\WorkerJobEvent;
use Resque\Component\Worker\Model\WorkerInterface;

/**
 * Resque Worker
 *
 * The worker handles querying issued queues for jobs, processing them and handling the result.
 */
class Worker implements
    WorkerInterface,
    LoggerAwareInterface
{
    /**
     * @var string Identifier of this worker.
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
     * @var JobInterface Current job being processed by this worker.
     */
    protected $currentJob = null;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var bool if the worker should fork to perform.
     */
    protected $fork = true;

    /**
     * Constructor
     *
     * @param JobInstanceFactoryInterface $jobInstanceFactory
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        JobInstanceFactoryInterface $jobInstanceFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->jobInstanceFactory = $jobInstanceFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function isShutdown()
    {
        return $this->shutdown;
    }

    /**
     * {@inheritdoc}
     */
    public function addQueue(QueueInterface $queue)
    {
        $this->queues[$queue->getName()] = $queue;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueues()
    {
        return $this->queues;
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

                if ($interval == 0) {
                    break;
                }

                usleep($interval * 1000000);

                continue;
            }

            try {
                $job = $this->reserve();
            }catch(\Exception $ex){
                $this->getLogger()->error('Failed to reserve due to exception "{message}", skipping', array('message'=>$ex->getMessage()));
                continue;
            }

            if (null === $job) {
                // For an interval of 0, break now - helps with unit testing etc
                // @todo replace with some method, which can be mocked... an interval of 0 should be considered valid
                if ($interval == 0) {
                    break;
                }

                $this->eventDispatcher->dispatch(
                    ResqueWorkerEvents::WAIT_NO_JOB,
                    new WorkerEvent($this)
                );

                $this->getProcess()->setTitle('Waiting for ' . implode(',', $this->queues));


                $this->getLogger()->debug('Sleeping for {seconds}s, no jobs on {queues}', array('queues'=>implode(', ',array_map(function($a){return $a->getName();}, $this->getQueues())), 'seconds'=>$interval));

                sleep($interval);

                continue;
            }

            $this->getLogger()->notice('Starting work on {job}', array('job' => $job));

            if ($job instanceof TrackableJobInterface) {
                $job->setState(JobInterface::STATE_PERFORMING);
            }

            $this->setCurrentJob($job);

            if ($this->fork) {
                $this->eventDispatcher->dispatch(
                    ResqueWorkerEvents::BEFORE_FORK_TO_PERFORM,
                    new WorkerJobEvent($this, $job)
                );

                $this->childProcess = $this->getProcess()->fork();

                if (null === $this->childProcess) {
                    // This is child process, it will perform the job and then die.
                    $this->eventDispatcher->dispatch(
                        ResqueWorkerEvents::AFTER_FORK_TO_PERFORM,
                        new WorkerJobEvent($this, $job)
                    );

                    // @todo do not construct Process here.
                    $child = new Process();
                    $child->setPidFromCurrentProcess();
                    $this->setProcess($child);

                    $this->perform($job);

                    exit(0);
                } else {
                    // This is the parent.
                    $title = 'Forked ' . $this->childProcess->getPid() . ' at ' . date('c');
                    $this->getProcess()->setTitle($title);
                    $this->getLogger()->debug($title);

                    // Wait until the child process finishes before continuing.
                    $this->childProcess->wait();

                    if (false === $this->childProcess->isCleanExit()) {
                        $exception = new DirtyExitException(
                            'Job dirty exited with code ' . $this->childProcess->getExitCode()
                        );

                        $this->handleFailedJob($job, $exception);
                    }
                }

                // Child should be dead by now.
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
     * @return JobInterface|null Instance of JobInterface if a job is found, null if not.
     */
    public function reserve()
    {
        foreach ($this->queues as $queue) {
            $this->getLogger()->debug(
                'Checking {queue} for jobs',
                array(
                    'queue' => $queue
                )
            );
            $job = $queue->dequeue();
            if (false === (null === $job)) {
                $this->getLogger()->info(
                    'Found job {job} on queue {queue}',
                    array(
                        'job' => $job,
                        'queue' => $queue,
                    )
                );

                return $job;
            }
        }

        return null;
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
        $status = sprintf('Performing job %s:%s', $job->getOriginQueue(), $job->getId());
        $this->getProcess()->setTitle($status);
        $this->getLogger()->info($status);

        if ($job instanceof TrackableJobInterface) {
            $job->setState(JobInterface::STATE_PERFORMING);
        }

        try {
            $jobInstance = $this->jobInstanceFactory->createPerformantJob($job);

            if (false === ($jobInstance instanceof PerformantJobInterface)) {
                throw new InvalidJobException(
                    'Job ' . $job->getId(). ' "' . get_class($jobInstance) . '" needs to implement Resque\JobInterface'
                );
            }

            $this->eventDispatcher->dispatch(
                ResqueJobEvents::BEFORE_PERFORM,
                new JobInstanceEvent($this, $job, $jobInstance)
            );

            $jobInstance->perform($job->getArguments());

        } catch (\Exception $exception) {
            $this->handleFailedJob($job, $exception);

            return false;
        }

        if ($job instanceof TrackableJobInterface) {
            $job->setState(JobInterface::STATE_COMPLETE);
        }

        $this->getLogger()->notice('{job} has successfully processed', array('job' => $job));

        $this->eventDispatcher->dispatch(ResqueJobEvents::PERFORMED, new WorkerJobEvent($this, $job));

        return true;
    }

    /**
     * Handle failed job
     *
     * @param JobInterface $job The job that failed.
     * @param \Exception $exception The reason the job failed.
     */
    protected function handleFailedJob(JobInterface $job, \Exception $exception)
    {
        if ($job instanceof TrackableJobInterface) {
            $job->setState(JobInterface::STATE_FAILED);
        }

        $this->getLogger()->error(
            'Perform failure on {job}, {message}',
            array(
                'job' => $job,
                'message' => $exception->getMessage(),
                'exception' => $exception
            )
        );

        $this->eventDispatcher->dispatch(
            ResqueJobEvents::FAILED,
            new JobFailedEvent($job, $exception, $this)
        );
    }

    /**
     * Notify Redis that we've finished working on a job, clearing the working
     * state and incrementing the job stats.
     *
     * @param JobInterface $job
     */
    protected function workComplete(JobInterface $job)
    {
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
            $this->setId($this->getHostname() . ':' . $this->getProcess()->getPid() . ':' . implode(',', $this->queues));
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

        $worker = $this;
        $signal_handler = function($cbname) use($worker){
            return function($signo) use($worker, $cbname){
                $worker->getLogger()->debug("Signal {signo} at pid:{pid} received, doing {action}", array('signo'=>$signo, 'action'=>$cbname, 'pid'=>getmypid()));
                return $worker->$cbname($signo);
            };
        };

        //Signal handlers
        pcntl_signal(SIGTERM, $signal_handler('halt'));
        pcntl_signal(SIGINT, $signal_handler('halt'));
        pcntl_signal(SIGQUIT, $signal_handler('stop'));
        pcntl_signal(SIGUSR1, $signal_handler('haltCurrentJob'));
        pcntl_signal(SIGUSR2, $signal_handler('pause'));
        pcntl_signal(SIGCONT, $signal_handler('resume'));

        $this->getLogger()->debug('Registered signals');
    }

    /**
     * Signal handler callback for USR2, pauses processing of new jobs.
     */
    public function pause()
    {
        $this->paused = true;

        if($this->getProcess()->getPid() == getmypid()) {
            $this->getLogger()->notice('SIGUSR2 received; pausing job processing');
        }else{
            $this->getProcess()->kill(SIGUSR2);
        }

    }

    /**
     * Signal handler callback for CONT, resumes worker allowing it to pick
     * up new jobs.
     */
    public function resume()
    {
        $this->paused = false;

        if($this->getProcess()->getPid() == getmypid()) {
            $this->getLogger()->notice('SIGCONT received; resuming job processing');
        }else{
            $this->getProcess()->kill(SIGCONT);
        }
    }

    /**
     * Force an immediate shutdown of the worker, killing any child jobs
     * currently running, or the job it is currently working on.
     */
    public function halt()
    {
        if($this->getProcess()->getPid() == getmypid()) {
            $this->stop();
            $this->haltCurrentJob();
        }else{
            $this->getProcess()->kill(SIGINT);
        }
    }

    /**
     * Schedule a worker for shutdown. Will finish processing the current job
     * and when the timeout interval is reached, the worker will shut down.
     */
    public function stop()
    {
        $this->shutdown = true;
        if($this->getProcess()->getPid() == getmypid()) {
            $this->getLogger()->notice('Worker {worker} shutting down', array('worker' => $this));
        }else{
            $this->getProcess()->kill(SIGQUIT);
        }
    }

    /**
     * Get worker ready to start again.
     */
    public function reset(){
        $this->setId(null);
        $this->shutdown = false;
        $this->getLogger()->notice('Worker {worker} reset', array('worker' => $this));
    }

    /**
     * Kill a forked child job immediately. The job it is processing will not
     * be completed.
     */
    public function haltCurrentJob()
    {
        $currentJob = $this->getCurrentJob();

        if (null === $this->childProcess || !$this->childProcess->getPid()) {
            $this->getLogger()->debug('No child to kill for worker {worker}', array('worker' => $this));

            return;
        }

        $this->getLogger()->warning(
            'Worker {worker} killing active child {childPid}',
            array(
                'worker' => $this,
                'childProcess' => $this->childProcess,
                'childPid' => $this->childProcess->getPid()
            )
        );

        $this->childProcess->kill();

        if (null !== $currentJob) {
            $this->handleFailedJob(
                $currentJob,
                new DirtyExitException(
                    sprintf(
                        'Worker %s forcibly killed job %s due to halt',
                        $this,
                        $currentJob->getId()
                    )
                )
            );
        }
    }

    /**
     * Set current job
     *
     * Sets which job the worker is currently working on, and records it in redis.
     *
     * @param JobInterface|null $job The job being worked on, or null if the worker isn't processing a job anymore.
     * @throws ResqueRuntimeException when current job is not cleared before setting a new one.
     * @return $this
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

        // @todo dispatch event, or maybe called WorkerRegistry->persist?

        return $this;
    }

    /**
     * The JobInterface this worker is currently working on.
     *
     * @return JobInterface|null The current Job this worker is processing,
     *                           null if currently not processing a job
     */
    public function getCurrentJob()
    {
        return $this->currentJob;
    }

    /**
     * Set fork to perform job
     *
     * @param bool $fork If the worker should fork to do work
     * @return $this
     */
    public function setForkOnPerform($fork)
    {
        $this->fork = (bool)$fork;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * {@inheritDoc}
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    public function __toString()
    {
        return $this->getId();
    }
}
