<?php

namespace Resque\Tests\Jobs;

use Resque\Job\PerformantJobInterface;

class PhpError implements PerformantJobInterface
{
    public function perform()
    {
        callToUndefinedFunction();
    }
}
