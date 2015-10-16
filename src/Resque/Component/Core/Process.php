<?php

namespace Resque\Component\Core;

use Resque\Component\Core\Exception\ResqueRuntimeException;

/**
 * @todo make use of SystemInterface.
 */
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

    /**
     * Constructor.
     *
     * @param string|null $pid The current system process ID.
     */
    public function __construct($pid = null)
    {
        $this->pid = $pid;
    }

    /**
     * Get PID.
     *
     * @return integer|null The current pid, if set.
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set PID.
     *
     * @deprecated This makes no sense when you consider that in theory Process is the thing that knows about
     *             and manages PIDs.
     *
     * @todo see Resque\Component\Core\Foreman::startWorker() it calls self::setPid().
     *
     * @param integer $pid A process id.
     * @return $this
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * Set PID from current system process.
     *
     * @return $this
     */
    public function setPidFromCurrentProcess()
    {
        $this->pid = getmypid();

        return $this;
    }

    /**
     * Fork.
     *
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

        $child = new self($pid);

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
     * @param $options
     * @return int The status code from the process exit.
     */
    public function wait($options = 0)
    {
        if (!$this->getPid()) {
            throw new ResqueRuntimeException(
                'Cannot wait on a process with out a pid set'
            );
        }

        return pcntl_waitpid($this->pid, $this->status, $options);
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

        //TODO: needs logger
        //$this->getLogger()->notice('Sending {signal} to process {pid}', array('signal'=>$signal, 'pid'=>$this->getPid()));

        // @todo Work out if I care to check the result, and if I did what to do about it... Exception?
        posix_kill($this->getPid(), $signal);

        $this->pid = null;

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
        if (getmypid() != $this->getPid()) {
            throw new ResqueRuntimeException(
                sprintf(
                    'Can not set title when process pid has changed. Expected "%s", got "%s"',
                    $this->getPid(),
                    getmypid()
                )
            );
        }

        // @todo, it is the workers domain to set this, move this logic back to the worker.
        $processTitle = 'resque-' . Resque::VERSION . ': ' . $title;

        if (function_exists('cli_set_process_title')) {
            // @todo remove @, it is throwing errors on my mac.
            @cli_set_process_title($processTitle);

            return;
        }

        if (function_exists('setproctitle')) {
            setproctitle($processTitle);
        }
    }
}
