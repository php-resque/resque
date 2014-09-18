<?php

namespace Resque\Tests\Jobs;

use Resque\Job\PerformantJobInterface;

class Simple implements PerformantJobInterface
{
    public static $called = false;

    public function perform()
    {
        self::$called = true;
    }
}
