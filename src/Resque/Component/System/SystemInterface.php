<?php
namespace Resque\Component\System;

use Resque\Component\Core\Process;

/**
 * Interface for system calls.
 */
interface SystemInterface
{
    /**
     * Get hostname.
     *
     * @return string The current name of the host.
     */
    public function getHostname();

    /**
     * Get the current PID.
     *
     * @return string The current PID.
     */
    public function getCurrentPid();

    /**
     * Create current process.
     *
     * @return Process A new Process object with the current PID set.
     */
    public function createCurrentProcess();
}
