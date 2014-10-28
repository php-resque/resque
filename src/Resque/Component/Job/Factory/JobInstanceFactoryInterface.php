<?php

namespace Resque\Component\Job\Factory;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\PerformantJobInterface;
use Resque\Job\Exception;

interface JobInstanceFactoryInterface
{
    /**
     * Create PerformantJobInterface class
     *
     * @throws Exception\JobNotFoundException When the job class/service could not be found.
     *
     * @param JobInterface $job
     * @return \Resque\Component\Job\PerformantJobInterface The actual class/service that will perform.
     */
    public function createJob(JobInterface $job);
}
