<?php

namespace Resque\Component\Job\Factory;

use Resque\Component\Job\Model\JobInterface;

interface JobInstanceFactoryInterface
{
    /**
     * Create PerformantJobInterface class
     *
     * @throws \Resque\Component\Job\Exception\JobNotFoundException When the job class/service could not be found.
     *
     * @param JobInterface $job
     * @return \Resque\Component\Job\PerformantJobInterface The actual class/service that will perform.
     */
    public function createPerformantJob(JobInterface $job);
}
