<?php

namespace Resque\Event;

use Resque\Job\JobInterface;
use Resque\WorkerInterface;
use Exception;

class JobFailedEvent implements EventInterface
{
    /**
     * @var \Exception
     */
    protected $exception;

    public function __construct(JobInterface $job, Exception $exception, WorkerInterface $worker)
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
