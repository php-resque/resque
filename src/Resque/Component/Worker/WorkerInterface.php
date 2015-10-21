<?php

namespace Resque\Component\Worker;

use Resque\Component\Core\Process;
use Resque\Component\Worker\Model\WorkerInterface as WorkerModel;

interface WorkerInterface
{
    /**
     * Get model.
     *
     * @return WorkerModel The model for this this worker.
     */
    public function getModel();

    /**
     * Work
     *
     * The worker should probably do some important stuff... like process jobs.
     */
    public function work();

    /**
     * Set process
     *
     * @param Process $process
     * @return $this
     */
    public function setProcess(Process $process);

    /**
     * Get process
     *
     * @return Process
     */
    public function getProcess();

    /**
     * Get current job.
     *
     * @return JobInterface|null The job this worker is currently processing, if one at all.
     */
    public function getCurrentJob();

    /**
     * Stop
     *
     * The worker will finish up the current job, if there is one at all, and then exit.
     *
     * @return $this
     */
    public function stop();

    /**
     * Halt
     *
     * The worker will immediately halt any current job and then exit.
     *
     * @return $this
     */
    public function halt();

    /**
     * Pause
     *
     * The worker will finish up the current job, if there is one at all,and then it will
     * wait and not request any new jobs.
     *
     * @return $this
     */
    public function pause();

    /**
     * Resume
     *
     * This allows the worker to begin processing jobs again, assuming the worker was paused.
     *
     * @return $this
     */
    public function resume();

    /**
     * Halt current job.
     *
     * If the worker is currently processing a job, it should immediately halt it causing it
     * to fail, and then continue normal operation.
     *
     * @return $this
     */
    public function haltCurrentJob();
}
