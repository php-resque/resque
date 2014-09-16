<?php

namespace Resque\Tests\Jobs;

use Resque\Job\JobInterface;

class DirtyExit implements JobInterface
{
    public function perform()
    {
        exit(1);
    }
}
