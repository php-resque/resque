<?php

namespace Resque\Job;

use Resque\Job as JobPayload;

class JobInstanceFactory implements JobInstanceFactoryInterface
{
    /**
     * Create PerformantJobInterface class
     *
     * @throws Exception\JobNotFoundException When the job class could not be found.
     *
     * @param JobPayload $payload
     * @return PerformantJobInterface The instance of the Job that will perform.
     */
    public function createJob(JobPayload $payload)
    {
        $class = $payload->getJobClass();

        if (false === class_exists($class)) {
            throw new Exception\JobNotFoundException(
                'Could not find job class "' . $class . '"'
            );
        }

        $instance = new $class;

        //$instance->job = $this;
//        $this->instance->args = $this->getArguments();
//        $this->instance->queue = $this->queue;

        return $instance;
    }
}
