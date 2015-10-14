<?php
namespace Resque\Component\System;


/**
 * Interface for system calls
 *
 * @todo getmypid and other system functions
 */
interface SystemInterface
{
    /**
     * Get hostname.
     *
     * @return string The current name of the host.
     */
    public function getHostname();
}
