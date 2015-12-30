<?php

namespace Resque\Component\Worker;

use Resque\Component\Core\Process;
use Resque\Component\Worker\Event\WorkerJobEvent;

/**
 * Resque Worker.
 *
 * The worker handles querying issued queues for jobs, processing them and handling the result.
 */
class WorkerOld
{

    /**
     * @var Process The workers current process.
     */
    protected $process;

    /**
     * @var Process|null The workers child process, if it currently has one.
     */
    protected $childProcess = null;

    /**
     * @var array Array of all associated queues for this worker.
     */
    protected $queues = array();

    /**
     * @var boolean True if on the next iteration, the worker should shutdown.
     */
    protected $shutdown = false;

    /**
     * @var boolean True if this worker is paused.
     */
    protected $paused = false;

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
}
