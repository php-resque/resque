<?php
namespace Resque\Component\System;

use Resque\Component\Core\Process;

/**
 * System.
 *
 * Interface for system calls. It's intended to make various system calls easy to swap out for different
 * operating systems.
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
