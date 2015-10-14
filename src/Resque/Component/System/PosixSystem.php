<?php

namespace Resque\Component\System;

/**
 * Interface to a standard POSIX system
 */
class PosixSystem implements SystemInterface
{
    /**
     * @var string The systems hostname
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
}
