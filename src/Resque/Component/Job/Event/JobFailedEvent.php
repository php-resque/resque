<?php

namespace Resque\Component\Job\Event;

use Exception;
use Resque\Component\Job\Model\JobInterface;

class JobFailedEvent extends JobEvent
{
    /**
     * @var Exception The reason the job failed.
     */
    protected $exception;

    public function __construct(JobInterface $job, Exception $exception)
    {
        parent::__construct($job);

        $this->exception = $exception;
    }

    /**
     * @return Exception
     */
    public function getException()
    {
        return $this->exception;
    }
}
