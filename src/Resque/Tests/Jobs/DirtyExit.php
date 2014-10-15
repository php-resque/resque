<?php

namespace Resque\Tests\Jobs;

use Resque\Job\PerformantJobInterface;

class DirtyExit implements PerformantJobInterface
{
    public function perform($arguments)
    {
        exit(1);
    }
}
