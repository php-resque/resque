<?php

namespace Resque\Component\Job\Tests\Jobs;

use Resque\Component\Job\PerformantJobInterface;

class DirtyExit implements PerformantJobInterface
{
    public function perform($arguments)
    {
        exit(1);
    }
}
