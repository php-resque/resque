<?php

namespace Resque\Tests\Jobs;

use Resque\Job\JobInterface;

class PhpError implements JobInterface
{
    public function perform()
    {
        callToUndefinedFunction();
    }
}
