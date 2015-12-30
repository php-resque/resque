<?php

namespace Resque\Component\Worker;

use Resque\Component\Core\Process;
use Resque\Component\Worker\Model\WorkerInterface;

interface WorkerProcessInterface
{
    /**
     * Get worker model.
     *
     * @return WorkerInterface
     */
    public function getModel();

    /**
     * Get process.
     *
     * @return Process
     */
    public function getProcess();

    /**
     * Start.
     *
     * The worker should probably do some important stuff... like process jobs.
     */
    public function start();

    /**
     * Stop.
     *
     * The worker will finish up the current job, if there is one at all, and then exit.
     *
     * @return void
     */
    public function stop();

    /**
     * Halt.
     *
     * The worker will immediately halt any current job and then exit.
     *
     * @return void
     */
    public function halt();

    /**
     * Pause.
     *
     * The worker will finish up the current job, if there is one at all,and then it will
     * wait and not request any new jobs.
     *
     * @return void
     */
    public function pause();

    /**
     * Resume.
     *
     * This allows the worker to begin processing jobs again, assuming the worker was paused.
     *
     * @return void
     */
    public function resume();

    /**
     * Halt current job.
     *
     * If the worker is currently processing a job, it should immediately halt it causing it
     * to fail, and then continue normal operation.
     *
     * @return void
     */
    public function haltCurrentJob();
}
