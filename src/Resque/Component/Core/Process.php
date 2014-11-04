<?php

namespace Resque\Component\Core;

use Resque\Component\Core\Exception\ResqueRuntimeException;

class Process
{
    /**
     * @var int|null Posix process id.
     */
    protected $pid;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var int
     */
    protected $exitCode;

    public function __construct()
    {
        $this->pid = null;
    }

    /**
     * @return integer|null The current pid, if set.
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param integer $pid A process id.
     *
     * @return $this
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    public function setPidFromCurrentProcess()
    {
        $this->setPid(getmypid());

        return $this;
    }

    /**
     * fork() helper method for php-resque
     *
     * @see pcntl_fork()
     *
     * @return Process|null An instance of Process representing the child in the parent, or null in the child.
     * @throws \Resque\Component\Core\Exception\ResqueRuntimeException when cannot fork, or fork failed.
     */
    public function fork()
    {
        if (!function_exists('pcntl_fork')) {
            throw new ResqueRuntimeException('pcntl_fork is not available');
        }

        $this->setPidFromCurrentProcess();

        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new ResqueRuntimeException(
                sprintf(
                    'Unable to fork child. %s',
                    pcntl_strerror(pcntl_get_last_error())
                )
            );
        }

        if (0 === $pid) {
            // Forked and we're the child, so return nothing.
            return null;
        }

        $child = new Process();
        $child->setPid($pid);

        return $child;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * Wait for process to exit/die
     *
     * @return $this
     */
    public function wait()
    {
        if (!$this->getPid()) {
            throw new ResqueRuntimeException(
                'Cannot wait on a process with out a pid set'
            );
        }

        pcntl_waitpid($this->pid, $this->status);

        return $this;
    }

    /**
     * Attempt to kill the process
     *
     * @param int $signal The signal to send to the process
     * @return $this
     */
    public function kill($signal = SIGKILL)
    {
        if (!$this->getPid()) {
            throw new ResqueRuntimeException(
                'Cannot kill a process with out a pid set'
            );
        }

        // @todo Work out if I care to check the result, and if I did what to do about it... Exception?
        posix_kill($this->getPid(), $signal);
        $this->setPid(null);

        return $this;
    }

    /**
     * @return bool True if the process exited cleanly, false otherwise.
     */
    public function isCleanExit()
    {
        $this->exitCode = pcntl_wexitstatus($this->status);

        return pcntl_wifexited($this->getStatus()) && ($this->getExitCode() === 0);
    }

    /**
     * Handle pending signals
     *
     * @return $this;
     */
    public function dispatchSignals()
    {
        pcntl_signal_dispatch();

        return $this;
    }

    /**
     * Set process title
     *
     * If possible, sets the title of the currently running process.
     *
     * It is used to indicate the current state of the worker.
     *
     * Only supported systems with the PECL proctitle module installed, or CLI SAPI > 5.5.
     *
     * @see http://pecl.php.net/package/proctitle
     * @see http://php.net/manual/en/function.cli-set-process-title.php
     *
     * @param string $title The new process title.
     */
    public function setTitle($title)
    {
        // @todo, it is the workers domain to set this, move this logic back to the worker.
        $processTitle = 'resque-' . Resque::VERSION . ': ' . $title;

        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($processTitle);

            return;
        }

        if (function_exists('setproctitle')) {
            setproctitle($processTitle);
        }
    }
}
