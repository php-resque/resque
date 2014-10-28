<?php

namespace Resque\Event;

use Exception;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Worker\Model\WorkerInterface;

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
