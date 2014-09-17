<?php

namespace Resque\Tests\Jobs;

use Resque\Job\JobInterface;

class Simple implements JobInterface
{
    public static $called = false;

    public function perform()
    {
        self::$called = true;
    }
}
