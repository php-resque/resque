<?php

namespace Resque\Component\Job\Tests\Jobs;

use Resque\Component\Job\PerformantJobInterface;

class Failure implements PerformantJobInterface
{
    public function perform($arguments)
    {
        throw new \Exception('Failure!');
    }
}
