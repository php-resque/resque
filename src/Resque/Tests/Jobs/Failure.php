<?php

namespace Resque\Tests\Jobs;

use Resque\Job\JobInterface;

class Failure implements JobInterface
{
    public function perform()
    {
        throw new \Exception('Failure!');
    }
}
