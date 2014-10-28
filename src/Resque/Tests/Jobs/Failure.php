<?php

namespace Resque\Tests\Jobs;

use Resque\Component\Job\PerformantJobInterface;

class Failure implements PerformantJobInterface
{
    public function perform($arguments)
    {
        throw new \Exception('Failure!');
    }
}
