<?php

namespace Resque\Tests\Job;

use Resque\Job\Job;
use Resque\Job\JobInterface;

class Simple implements JobInterface
{
    public static  $called = false;

    public function perform()
    {
        self::$called = true;
    }


    /**
     * @param Job $job
     * @return mixed
     */
    public function setJob(Job $job)
    {
        // TODO: Implement setJob() method.
    }
}
