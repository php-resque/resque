<?php

namespace Resque\Event;

use Resque\Job;
use Resque\Worker;
use Exception;

class JobFailedEvent implements EventInterface
{
    public function __construct(Job $payload, Worker $worker, Exception $exception = null)
    {

    }

    public function getName()
    {
        return 'resque.job.failed';
    }
}
