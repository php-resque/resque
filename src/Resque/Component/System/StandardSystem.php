<?php

namespace Resque\Component\System;

use Resque\Component\Core\Process;

/**
 * Interface to a standard system
 */
class StandardSystem implements SystemInterface
{
    /**
     * @var string The systems hostname.
     */
    protected $hostname;

    /**
     * {@inheritDoc}
     */
    public function getHostname()
    {
        if ($this->hostname !== null) {
            return $this->hostname;
        }

        if (function_exists('gethostname')) {
            $this->hostname = gethostname();
        } else {
            $this->hostname = php_uname('n');
        }

        return $this->hostname;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentPid()
    {
        return $this->createCurrentProcess()->getPid();
    }

    /**
     * {@inheritDoc}
     */
    public function createCurrentProcess()
    {
        $process = new Process();
        $process->setPidFromCurrentProcess();

        return $process;
    }
}
