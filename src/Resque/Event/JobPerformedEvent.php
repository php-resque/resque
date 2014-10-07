<?php

namespace Resque\Event;

use Resque\Job\JobInterface;

class JobPerformedEvent implements EventInterface
{
    /**
     * @var JobInterface The job that just successfully performed!
     */
    protected $job;

    public function __constructor(JobInterface $job)
    {
        $this->job = $job;
    }

    public function getName()
    {
        return 'resque.job.performed';
    }

    /**
     * @return JobInterface
     */
    public function getJob()
    {
        return $this->job;
    }
}
