<?php
namespace Resque\Component\System;

/**
 * Interface to a standard POSIX system
 * @todo getmypid and other system functions
 *
 * @package Resque\Component\System
 */
class StandardSystem implements SystemInterface
{
    /**
     * @var string The systems hostname
     */
    protected $hostname;

    function getHostname(){
        if($this->hostname !== null){
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