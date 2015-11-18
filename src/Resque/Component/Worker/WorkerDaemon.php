<?php

namespace Resque\Component\Worker;

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
use Resque\Component\Worker\Event\WorkerDaemonEvent;
use Resque\Component\Worker\Event\WorkerDaemonSignalEvent;
use Resque\Component\Worker\Event\WorkerEvent;
use Resque\Component\Worker\Event\WorkerJobEvent;
use Resque\Component\Worker\Model\WorkerInterface;

/**
 * Resque Worker.
 *
 * The worker handles querying issued queues for jobs, processing them and handling the result.
 */
class WorkerDaemon implements
    WorkerDaemonInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var Process
     */
    protected $process;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        WorkerInterface $workerModel
    ) {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Get worker model.
     *
     * @return WorkerInterface
     */
    public function getModel()
    {
        // TODO: Implement getModel() method.
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
        $this->getProcess()->setTitle('Starting');

        $this->registerSignalHandlers();

        $this->eventDispatcher->dispatch(
            ResqueWorkerEvents::STARTED,
            new WorkerDaemonEvent($this)
        );

        $this->work();
    }

    /**
     * Stop.
     *
     * The worker will finish up the current job, if there is one at all, and then exit.
     *
     * @return void
     */
    public function stop()
    {
        // TODO: Implement stop() method.
    }

    /**
     * Halt.
     *
     * The worker will immediately halt any current job and then exit.
     *
     * @return void
     */
    public function halt()
    {
        // TODO: Implement halt() method.
    }

    /**
     * Pause.
     *
     * The worker will finish up the current job, if there is one at all,and then it will
     * wait and not request any new jobs.
     *
     * @return void
     */
    public function pause()
    {
        // TODO: Implement pause() method.
    }

    /**
     * Resume.
     *
     * This allows the worker to begin processing jobs again, assuming the worker was paused.
     *
     * @return void
     */
    public function resume()
    {
        // TODO: Implement resume() method.
    }

    /**
     * Halt current job.
     *
     * If the worker is currently processing a job, it should immediately halt it causing it
     * to fail, and then continue normal operation.
     *
     * @return void
     */
    public function haltCurrentJob()
    {
        // TODO: Implement haltCurrentJob() method.
    }

    /**
     * Register signal handlers.
     *
     * The following signals correspond to the following actions.
     *
     * TERM: Shutdown immediately and stop processing jobs.
     * INT: Shutdown immediately and stop processing jobs.
     * QUIT: Shutdown after the current job finishes processing.
     * USR1: Kill the forked child immediately and continue processing jobs.
     */
    protected function registerSignalHandlers()
    {
        // @todo add signal handler method to Process and remove pcntl calls from here!

        if (!function_exists('pcntl_signal')) {
            return;
        }

        declare(ticks = 100);

        $workerDaemon = $this;
        $eventDispatcher = $this->eventDispatcher;

        $signalHandler = function ($method) use ($workerDaemon, $eventDispatcher) {
            return function ($signal) use ($workerDaemon, $method, $eventDispatcher) {
                $eventDispatcher->dispatch(
                    ResqueWorkerEvents::DAEMON_SIGNAL_RECEIVED,
                    new WorkerDaemonSignalEvent($workerDaemon, $signal)
                );

                return $workerDaemon->$method($signal);
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

            $this->getProcess()->dispatchSignals();

            if ($this->isPaused) {
                $this->getProcess()->setTitle('Paused');

                $waitEvent = new WorkerDaemonWaitEvent($this);
                $this->eventDispatcher->dispatch(ResqueWorkerEvents::WAIT_PAUSED, $waitEvent);
                $this->wait($waitEvent->getSecondsToWait());

                continue;
            }

            $job = $this->

            try {
                $job = $this->reserve();
            } catch (\Exception $ex) {
                $this->eventDispatcher->dispatch(ResqueWorkerEvents::WAIT_NO_JOB, new WorkerDaemonEvent());

                continue;
            }

            if (null === $job) {
                $waitEvent = new WorkerDaemonWaitEvent($this);
                $this->eventDispatcher->dispatch(ResqueWorkerEvents::WAIT_NO_JOB, $waitEvent);
                $this->wait($waitEvent->getSecondsToWait());

                continue;
            }

            $this->setCurrentJob($job);

            if ($this->shouldForkToForm) {
                $this->eventDispatcher->dispatch(ResqueWorkerEvents::BEFORE_FORK_TO_PERFORM, new WorkerJobEvent($this, $job));

                $this->childProcess = $this->getProcess()->fork();

                if (null === $this->childProcess) {
                    // This is child process, it will perform the job and then die.
                    $this->eventDispatcher->dispatch(ResqueWorkerEvents::AFTER_FORK_TO_PERFORM, new WorkerJobEvent($this, $job));

                    // @todo do not construct Process here. clone maybe?
                    $child = new Process();
                    $child->setPidFromCurrentProcess();
                    $this->setProcess($child);

                    $this->perform($job);

                    exit(0);
                } else {
                    // This is the parent.
                    $title = 'Forked ' . $this->childProcess->getPid() . ' at ' . date('c');
                    $this->getProcess()->setTitle($title);

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

    protected function wait($interval)
    {
        usleep($interval * 1000000);
    }

    protected function perform(JobInterface $job)
    {

    }
}
