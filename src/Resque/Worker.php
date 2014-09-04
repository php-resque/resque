<?php

namespace Resque;

use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Resque\Event\EventDispatcher;
use Resque\Event\JobBeforePerformEvent;
use Resque\Event\JobFailedEvent;
use Resque\Event\JobAfterPerformEvent;
use Resque\Event\JobPerformedEvent;
use Resque\Job\DirtyExitException;
use Resque\Job\Exception\DontPerformException;
use Resque\Job\Exception\InvalidJobException;
use Resque\Job\JobFactory;
use Resque\Job\JobInterface;
use Resque\Job\Status;

/**
 * Resque Worker
 *
 * The worker handles querying it issued queues for jobs, running them and handling the result.
 */
class Worker
{
    public $pid;

    /**
     * @var LoggerInterface Logging object that impliments the PSR-3 LoggerInterface
     */
    public $logger;

    /**
     * @var array Array of all associated queues for this worker.
     */
    private $queues = array();

    /**
     * @var string The hostname of this worker.
     */
    private $hostname;

    /**
     * @var boolean True if on the next iteration, the worker should shutdown.
     */
    private $shutdown = false;

    /**
     * @var boolean True if this worker is paused.
     */
    private $paused = false;

    /**
     * @var string String identifying this worker.
     */
    private $id;

    /**
     * @var Job Current job, if any, being processed by this worker.
     */
    private $currentJob = null;

    /**
     * @var int Process ID of child worker processes.
     */
    private $childPid = null;

    /**
     * @var Event\EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * Constructor
     *
     * Instantiate a new worker, given a list of queues that it should be working
     * on. The list of queues should be supplied in the priority that they should
     * be checked for jobs (first come, first served)
     *
     * Passing a single '*' allows the worker to work on all queues in alphabetical
     * order. You can easily add new queues dynamically and have them worked on using
     * this method.
     *
     * @param Queue|Queue[] $queues String with a single queue name, array with multiple.
     * @param null $jobFactory
     */
    public function __construct($queues = null, $jobFactory = null)
    {
        $this->jobFactory = $jobFactory ?: new JobFactory();
        $this->eventDispatcher = new EventDispatcher();
        $this->logger = new Log();

        if (false === (null === $queues)) {
            if (!is_array($queues)) {
                $this->addQueue($queues);
            } else {
                $this->addQueues($queues);
            }
        }

        if (function_exists('gethostname')) {
            $hostname = gethostname();
        } else {
            $hostname = php_uname('n');
        }

        $this->hostname = $hostname;
        $this->id = uniqid($this->hostname) . ':' . getmypid() . ':' . implode(',', $this->queues);
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
     * @param QueueInterface $queue
     * @param null $alias
     * @return $this
     */
    public function addQueue(QueueInterface $queue, $alias = null)
    {
        $this->queues[$alias] = $queue;

        return $this;
    }

    public function addQueues($queues)
    {
        foreach ($queues as $alias => $queue) {
            $this->addQueue($queue, $alias);
        }

        return $this;
    }

    public function getQueue($alias = null)
    {
        return $this->queues[$alias];
    }

    public function getAllQueues()
    {
        return $this->queues;
    }

    /**
     * Set the ID of this worker to a given ID string.
     *
     * @param string $workerId ID for the worker.
     */
    public function setId($workerId)
    {
        $this->id = $workerId;
    }

    public function getId()
    {
       return uniqid($this->hostname) . ':' . getmypid() . ':' . implode(',', $this->queues);
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
        $this->updateProcTitle('Starting');

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
                if ($interval == 0) {

                    break;
                }

                if ($blocking === false) {
                    // If no job was found, we sleep for $interval before continuing and checking again
                    $this->logger->log(LogLevel::DEBUG, 'Sleeping for {interval}', array('interval' => $interval));
                    if ($this->paused) {
                    } else {
                        $this->updateProcTitle('Waiting for ' . implode(',', $this->queues));
                    }

                    usleep($interval * 1000000);
                }

                continue;
            }

            $this->logger->notice(
                sprintf(
                    'Starting work on %s',
                    $job
                ),
                array('job' => $job)
            );

            //Resque_Event::trigger('beforeFork', $job);
            $this->workingOn($job);

            $this->childPid = Foreman::fork();

            // if forking, and forked, or not forking run the job.
            if ($this->childPid === 0 || $this->childPid === false) {
                // Forked and we're the child, or not forking.

                $this->perform($job);

                if ($this->childPid === 0) {
                    exit(0);
                }
            }

            // if forking and forked, wait for child
            if ($this->childPid > 0) {
                // Parent process, sit and wait
                $status = 'Forked ' . $this->childPid . ' at ' . strftime('%F %T');
                $this->updateProcTitle($status);
                $this->logger->log(LogLevel::DEBUG, $status);

                // Wait until the child process finishes before continuing
                pcntl_wait($status);
                $exitStatus = pcntl_wexitstatus($status);
                if ($exitStatus !== 0) {
                    $job->fail(
                        new DirtyExitException(
                            'Job exited with exit code ' . $exitStatus
                        )
                    );
                }
            }

            $this->childPid = null;
            $this->doneWorking();
        }
    }

    /**
     * Process a single job.
     *
     * @param Job $job The job to be processed.
     * @return void
     */
    public function perform(Job $job)
    {
        $status = 'Performing Job ' . $job;
        $this->updateProcTitle($status);
        $this->logger->log(LogLevel::INFO, $status);

        try {
            $jobInstance = $this->jobFactory->createJob($job);

            $this->eventDispatcher->dispatch(
                new JobBeforePerformEvent($job, $jobInstance)
            );

            $jobInstance->perform();

            $this->eventDispatcher->dispatch(
                new JobAfterPerformEvent($job)
            );
        } catch (DontPerformException $exception) {
            //Resque_Event::trigger('resque.job.dont_perform', $job);  @todo restore

            // @todo work out the value in a DontPerformException

            return;
        } catch (\Exception $exception) {
            $this->logger->error(
                '{job} has failed, {stack}', // @todo improve error message.
                array(
                    'job' => $job,
                    'stack' => $exception->getMessage()
                )
            );

            // $job->fail($e); @todo Restore failure behaviour.

            $this->eventDispatcher->dispatch(
                new JobFailedEvent($job, $exception)
            );

            return;
        }

        // $job->updateStatus(Status::STATUS_COMPLETE); @todo update status behaviour

        $this->logger->notice('{job} has successfully processed', array('job' => $job));

        $this->eventDispatcher->dispatch(
            new JobPerformedEvent($job)
        );
    }

    /**
     * @todo The name reserve doesn't sit well, all it's doing is asking queues for jobs. Change it.
     *
     * @param  bool $blocking
     * @param  int $timeout
     * @return Job|null Instance of Job if a job is found, null if not.
     */
    public function reserve($blocking = false, $timeout = null)
    {
        $queues = $this->queues();

        if (!is_array($queues)) {
            return null;
        }

        if ($blocking === true) {
            $payload = Job::reserveBlocking($queues, $timeout);
            if ($payload) {
                $this->logger->log(LogLevel::INFO, 'Found job on {queue}', array('queue' => $payload->queue));

                return $payload;
            }
        } else {
            foreach ($queues as $queue) {
                $this->logger->log(LogLevel::DEBUG, 'Checking {queue} for jobs', array('queue' => $queue));
                $payload = $queue->pop();
                if ($payload) {
                    $this->logger->log(LogLevel::INFO, 'Found job on {queue}', array('queue' => $queue));

                    return $payload;
                }
            }
        }

        return null;
    }

    /**
     * Return an array containing all of the queues that this worker should use
     * when searching for jobs.
     *
     * If * is found in the list of queues, every queue will be searched in
     * alphabetic order. (@see $fetch)
     *
     * @param boolean $fetch If true, and the queue is set to *, will fetch
     * all queue names from redis.
     * @return array Array of associated queues.
     */
    public function queues($fetch = true)
    {
        if (!in_array('*', $this->queues) || $fetch == false) {
            return $this->queues;
        }

        $queues = Resque::queues();
        sort($queues);
        return $queues;
    }

    /**
     * Perform necessary actions to start a worker.
     */
    private function startup()
    {
        $this->registerSigHandlers();
        // $this->pruneDeadWorkers(); @todo not a workers problem, Foremans.
        //Resque_Event::trigger('beforeFirstFork', $this);
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
        $this->logger->log(LogLevel::DEBUG, 'Registered signals');
    }

    /**
     * Signal handler callback for USR2, pauses processing of new jobs.
     */
    public function pauseProcessing()
    {
        $this->logger->log(LogLevel::NOTICE, 'USR2 received; pausing job processing');
        $this->paused = true;
    }

    /**
     * Signal handler callback for CONT, resumes worker allowing it to pick
     * up new jobs.
     */
    public function resumeProcessing()
    {
        $this->logger->log(LogLevel::NOTICE, 'CONT received; resuming job processing');
        $this->paused = false;
    }

    /**
     * Schedule a worker for shutdown. Will finish processing the current job
     * and when the timeout interval is reached, the worker will shut down.
     */
    public function shutdown()
    {
        $this->shutdown = true;
        $this->logger->log(LogLevel::NOTICE, 'Shutting down');
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
     * Kill a forked child job immediately. The job it is processing will not
     * be completed.
     */
    public function killChild()
    {
        if (!$this->childPid) {
            $this->logger->log(LogLevel::DEBUG, 'No child to kill.');
            return;
        }

        $this->logger->log(LogLevel::INFO, 'Killing child at {child}', array('child' => $this->childPid));
        if (exec('ps -o pid,state -p ' . $this->childPid, $output, $returnCode) && $returnCode != 1) {
            $this->logger->log(LogLevel::DEBUG, 'Child {child} found, killing.', array('child' => $this->childPid));
            posix_kill($this->childPid, SIGKILL);
            $this->childPid = null;
        } else {
            $this->logger->log(LogLevel::INFO, 'Child {child} not found, restarting.', array('child' => $this->childPid));
            $this->shutdown();
        }
    }

    /**
     * Tell Redis which job we're currently working on.
     *
     * @param Job $job The job we're working on.
     */
    protected function workingOn(Job $job)
    {
        //$job->worker = $this;
        $this->currentJob = $job;
        $job->updateStatus(Status::STATUS_RUNNING);
        $data = json_encode(
            array(
                'queue' => $job->queue,
                'run_at' => strftime('%a %b %d %H:%M:%S %Z %Y'),
                'payload' => $job->jsonSerialize()
            )
        );

        Resque::redis()->set('worker:' . $this, $data);
    }

    /**
     * Notify Redis that we've finished working on a job, clearing the working
     * state and incrementing the job stats.
     */
    protected function doneWorking()
    {
        $this->currentJob = null;
        Stat::incr('processed');
        Stat::incr('processed:' . (string) $this);
        Resque::redis()->del('worker:' . (string)$this);
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
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
