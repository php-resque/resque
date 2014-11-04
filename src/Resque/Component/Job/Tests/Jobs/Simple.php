<?php

namespace Resque\Component\Job\Tests\Jobs;

use Resque\Component\Job\PerformantJobInterface;

class Simple implements PerformantJobInterface
{
    public static $called = false;
    public static $lastPerformArguments = null;

    public function perform($arguments)
    {
        self::$called = true;
        self::$lastPerformArguments = $arguments;
    }
}
