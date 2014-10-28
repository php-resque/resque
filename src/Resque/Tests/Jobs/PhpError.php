<?php

namespace Resque\Tests\Jobs;

use Resque\Component\Job\PerformantJobInterface;

class PhpError implements PerformantJobInterface
{
    public function perform($arguments)
    {
        callToUndefinedFunction();
    }
}
