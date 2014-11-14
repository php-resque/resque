<?php

namespace Resque\Component\Job\Event;

use Resque\Component\Job\Model\JobInterface;

class JobEvent
{
    /**
     * @var JobInterface The job.
     */
    protected $job;

    public function __construct(JobInterface $job)
    {
        $this->job = $job;
    }

    /**
     * @return JobInterface
     */
    public function getJob()
    {
        return $this->job;
    }
}
