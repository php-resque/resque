<?php

namespace Resque\Event;

use Resque\Job;
use Resque\Worker;
use Exception;

class JobFailedEvent implements EventInterface
{
    /**
     * @var \Exception
     */
    protected $exception;

    public function __construct(Job $payload, Worker $worker, Exception $exception = null)
    {
        $this->exception = $exception;
    }

    public function getName()
    {
        return 'resque.job.failed';
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }
}
