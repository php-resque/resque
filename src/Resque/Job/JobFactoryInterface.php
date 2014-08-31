<?php

namespace Resque\Job;

use Resque\Job;

/**
 *
 */
interface JobFactoryInterface
{
    /**
     * Create job class
     *
     * @throws Exception\JobNotFoundException When the job's class could not be found.
     *
     * @param Job $job
     * @return object The class that will perform.
     */
    public function createJob(Job $job);
}
