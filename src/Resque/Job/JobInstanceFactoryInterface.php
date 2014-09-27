<?php

namespace Resque\Job;

use Resque\Job\JobInterface;

/**
 *
 */
interface JobInstanceFactoryInterface
{
    /**
     * Create PerformantJobInterface class
     *
     * @throws Exception\JobNotFoundException When the job class/service could not be found.
     *
     * @param JobInterface $job
     * @return PerformantJobInterface The instance of the Job that will perform.
     */
    public function createJob(JobInterface $job);
}
