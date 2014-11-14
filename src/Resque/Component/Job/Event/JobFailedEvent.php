<?php

namespace Resque\Component\Job\Event;

use Exception;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Worker\Model\WorkerInterface;

class JobFailedEvent extends JobEvent
{
    /**
     * @var Exception The reason the job failed.
     */
    protected $exception;

    /**
     * @var WorkerInterface The worker the job failed with in.
     */
    protected $worker;

    public function __construct(JobInterface $job, Exception $exception, WorkerInterface $worker)
    {
        parent::__construct($job);
        $this->exception = $exception;
        $this->worker = $worker;
    }

    /**
     * @return Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return WorkerInterface
     */
    public function getWorker()
    {
        return $this->worker;
    }
}
