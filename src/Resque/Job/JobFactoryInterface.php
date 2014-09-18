<?php

namespace Resque\Job;

use Resque\Job as JobPayload;

/**
 *
 */
interface JobFactoryInterface
{
    /**
     * Create PerformantJobInterface class
     *
     * @throws Exception\JobNotFoundException When the job class/service could not be found.
     *
     * @param JobPayload $payload
     * @return PerformantJobInterface The instance of the Job that will perform.
     */
    public function createJob(JobPayload $payload);
}
