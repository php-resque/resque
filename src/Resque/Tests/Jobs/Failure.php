<?php

namespace Resque\Tests\Jobs;

use Resque\Job\PerformantJobInterface;

class Failure implements PerformantJobInterface
{
    public function perform()
    {
        throw new \Exception('Failure!');
    }
}
