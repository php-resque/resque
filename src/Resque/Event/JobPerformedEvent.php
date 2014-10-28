<?php

namespace Resque\Event;

use Resque\Component\Job\Model\JobInterface;

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
     * @return \Resque\Component\Job\JobInterface
     */
    public function getJob()
    {
        return $this->job;
    }
}
