<?php

namespace Resque\Job;

use Resque\Job as JobPayload;

/**
 *
 */
interface JobFactoryInterface
{
    /**
     * Create JobInterface class
     *
     * @throws Exception\JobNotFoundException When the job class/service could not be found.
     *
     * @param JobPayload $payload
     * @return JobInterface The instance of the Job that will perform.
     */
    public function createJob(JobPayload $payload);
}
