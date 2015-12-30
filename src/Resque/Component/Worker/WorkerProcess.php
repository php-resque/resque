<?php

namespace Resque\Component\Worker;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Core\Exception\ResqueRuntimeException;
use Resque\Component\Core\Process;
use Resque\Component\Job\Exception\DirtyExitException;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\System\SystemInterface;
use Resque\Component\Worker\Event\WorkerJobEvent;
use Resque\Component\Worker\Event\WorkerProcessEvent;
use Resque\Component\Worker\Event\WorkerProcessSignalEvent;
use Resque\Component\Worker\Event\WorkerProcessWaitEvent;
use Resque\Component\Worker\Model\WorkerInterface;

/**
 * Resque worker process.
 *
 * The worker handles querying issued queues for jobs, processing them and handling the result.
 */
class WorkerProcess implements
    WorkerProcessInterface
{
    /**
     * @var bool If TRUE, the worker will fork a child process to perform the job.
     */
    protected $shouldForkToPerform = true;

    /**
     * @var bool
     */
    protected $shouldContinueWork = true;

    /**
     * @var bool
     */
    protected $isPaused = false;

    /**
     * @var WorkerInterface
     */
    protected $model;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var JobPerformer
     */
    protected $jobPerformer;

    /**
     * @var SystemInterface
     */
    protected $system;

    /**
     * @var Process|null
     */
    protected $process;

    /**
     * @var Process|null The child process this worker is performing jobs, if currently doing so.
     */
    protected $childProcess;

    /**
     * Constructor.
     *
     * @param WorkerInterface $model
     * @param EventDispatcherInterface $eventDispatcher
     * @param JobPerformer $jobPerformer
     * @param SystemInterface $system
     */
    public function __construct(
        WorkerInterface $model,
        EventDispatcherInterface $eventDispatcher,
        JobPerformer $jobPerformer,
        SystemInterface $system
    ) {
        $this->model = $model;
        $this->eventDispatcher = $eventDispatcher;
        $this->jobPerformer = $jobPerformer;
        $this->system = $system;
    }

    /**
     * Set fork to perform.
     *
     * @param bool $fork TRUE if the worker should fork to do work, FALSE otherwise.
     * @return void
     */
    public function setForkToPerform($fork)
    {
        $this->shouldForkToPerform = (bool)$fork;
    }

    /**
     * {@inheritDoc}
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * {@inheritDoc}
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * {@inheritDoc}
     */
    public function start()
    {
        $this->process->setTitle('Starting');

        $this->registerSignalHandlers();

        $this->eventDispatcher->dispatch(ResqueWorkerEvents::STARTED, new WorkerProcessEvent($this));

        $this->work();
    }

    /**
     * {@inheritDoc}
     */
    public function stop()
    {
        $this->shouldContinueWork = false;
    }

    /**
     * {@inheritDoc}
     */
    public function halt()
    {
        $this->stop();
        $this->haltCurrentJob();
    }

    /**
     * {@inheritDoc}
     */
    public function pause()
    {
        $this->isPaused = true;
    }

    /**
     * {@inheritDoc}
     */
    public function resume()
    {
        $this->isPaused = false;
    }

    /**
     * {@inheritDoc}
     */
    public function haltCurrentJob()
    {
        $currentJob = $this->model->getCurrentJob();

        if (null === $this->childProcess || !$this->childProcess->getPid()) {
            // @todo event.
//            $this->getLogger()->debug('No child to kill for worker {worker}', array('worker' => $this));

            return;
        }

//        $this->getLogger()->warning(
//            'Worker {worker} killing active child {childPid}',
//            array(
//                'worker' => $this,
//                'childProcess' => $this->childProcess,
//                'childPid' => $this->childProcess->getPid()
//            )
//        );

        $this->childProcess->kill();

        if (null !== $currentJob) {
            $this->jobPerformer->handleFailedJob(
                $currentJob,
                new DirtyExitException(
                    sprintf(
                        'Worker "%s" forcibly killed job "%s" due to halt child call',
                        $this,
                        $currentJob->getId()
                    )
                )
            );
        }
    }

    /**
     * Register signal handlers.
     *
     * The following signals correspond to the following actions.
     *
     * TERM: Shutdown immediately and stop processing jobs.
     * INT : Shutdown immediately and stop processing jobs.
     * QUIT: Shutdown after the current job finishes processing.
     * USR1: Kill the forked child immediately and continue processing jobs.
     * USR2: Pause the worker.
     * CONT: If paused, resumes the worker.
     */
    protected function registerSignalHandlers()
    {
        // @todo add signal handler method to Process and remove pcntl calls from here!

        if (!function_exists('pcntl_signal')) {
            return;
        }

        declare(ticks = 100); // @todo should I decided this?

        $workerProcess = $this;
        $eventDispatcher = $this->eventDispatcher;

        $signalHandler = function ($method) use ($workerProcess, $eventDispatcher) {
            return function ($signal) use ($workerProcess, $method, $eventDispatcher) {
                $eventDispatcher->dispatch(
                    ResqueWorkerEvents::PROCESS_SIGNAL_RECEIVED,
                    new WorkerProcessSignalEvent($workerProcess, $signal)
                );

                return $workerProcess->$method($signal);
            };
        };

        // Signal handlers
        pcntl_signal(SIGTERM, $signalHandler('halt'));
        pcntl_signal(SIGINT, $signalHandler('halt'));
        pcntl_signal(SIGQUIT, $signalHandler('stop'));
        pcntl_signal(SIGUSR1, $signalHandler('haltCurrentJob'));
        pcntl_signal(SIGUSR2, $signalHandler('pause'));
        pcntl_signal(SIGCONT, $signalHandler('resume'));
    }

    /**
     * Work.
     *
     * The primary loop for a worker which when called on an instance starts
     * the worker's life cycle.
     */
    protected function work()
    {
        $this->shouldContinueWork = true;

        while ($this->shouldContinueWork) {

            $this->process->dispatchSignals();

            if ($this->isPaused) {
                $waitEvent = new WorkerProcessWaitEvent($this);
                $this->eventDispatcher->dispatch(ResqueWorkerEvents::PROCESS_WAIT_PAUSED, $waitEvent);

                $this->wait($waitEvent->getMsToWait());

                continue;
            }

            try {
                $job = $this->dequeueJob();
            } catch (\Exception $dequeueException) {
                $this->eventDispatcher->dispatch(ResqueWorkerEvents::JOB_DEQUEUE_FAILED, new WorkerProcessEvent($this));

                continue;
            }

            if (null === $job) {
                $waitEvent = new WorkerProcessWaitEvent($this);
                $this->eventDispatcher->dispatch(ResqueWorkerEvents::PROCESS_WAIT_NO_JOB, $waitEvent);

                $this->wait($waitEvent->getMsToWait());

                continue;
            }

            $this->performJob($job);
        }
    }

    /**
     * Wait.
     *
     * @param int $interval ms to wait.
     */
    protected function wait($interval)
    {
        usleep($interval);
    }

    /**
     * Dequeue Job.
     *
     * @return JobInterface|NULL instance of JobInterface if a job is found, otherwise NULL.
     */
    protected function dequeueJob()
    {
        foreach ($queues = $this->getModel()->getAssignedQueues() as $queue) {
            $job = $queue->dequeue();

            if (false === (null === $job)) {
                return $job;
            }
        }

        return null;
    }

    /**
     * Set current job.
     *
     * Sets which job the worker is currently working on.
     *
     * @param JobInterface|null $job The job being worked on, or null if the worker isn't processing a job anymore.
     * @throws ResqueRuntimeException when current job is not cleared before setting a new one.
     *
     * @return void
     */
    protected function setCurrentJob(JobInterface $job = null)
    {
        $currentJob = $this->model->getCurrentJob();

        if (null !== $job && null !== $currentJob) {
            throw new ResqueRuntimeException(
                sprintf(
                    'Cannot set current job to %s when current job is not null, current job is %s',
                    $job,
                    $currentJob
                )
            );
        }

        $this->model->setCurrentJob($job);

        // @todo dispatch event, or maybe called WorkerRegistry->persist?
    }

    /**
     * Perform job.
     *
     * Either passes the job of to the job performer, or forks a child and then off loads it.
     *
     * @param JobInterface $job
     *
     * @return void
     */
    protected function performJob(JobInterface $job)
    {
        $this->setCurrentJob($job);

        if ($this->shouldForkToPerform) {
            $this->eventDispatcher->dispatch(ResqueWorkerEvents::BEFORE_FORK_TO_PERFORM, new WorkerJobEvent($this, $job));

            $this->childProcess = $this->process->fork();

            if (null === $this->childProcess) {
                // This is child process, it will perform the job and then die.
                $this->eventDispatcher->dispatch(ResqueWorkerEvents::AFTER_FORK_TO_PERFORM, new WorkerJobEvent($this, $job));

                $this->process = $this->system->createCurrentProcess();

                $this->jobPerformer->perform($job);

                // @todo throw worker failed to perform exception.

                exit(0);
            } else {
                // This is the parent.
                $title = 'Forked ' . $this->childProcess->getPid() . ' at ' . date('c');
                $this->process->setTitle($title);

                // Wait until the child process finishes before continuing.
                $this->childProcess->wait();

                if (false === $this->childProcess->isCleanExit()) {
                    $exception = new DirtyExitException(
                        'Child process had job dirty exited with code ' . $this->childProcess->getExitCode()
                    );

                    $this->jobPerformer->handleFailedJob($job, $exception);

                    // @todo throw worker failed to perform exception.
                }
            }

            // Child should be dead by now.
            $this->childProcess = null;
        } else {
            $this->jobPerformer->perform($job);

            // @todo throw worker failed to perform exception.
        }

        $this->setCurrentJob(null);
    }
}
