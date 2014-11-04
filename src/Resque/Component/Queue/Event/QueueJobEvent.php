<?php

namespace Resque\Component\Queue\Event;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Model\QueueInterface;

class QueueJobEvent extends QueueEvent
{
    /**
     * @var JobInterface
     */
    protected $job;

    public function __construct(QueueInterface $queue, JobInterface $job)
    {
        parent::__construct($queue);

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
