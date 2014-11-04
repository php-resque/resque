<?php

namespace Resque\Component\Job\Factory;

use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\PerformantJobInterface;
use Resque\Job\Exception;

class JobInstanceFactory implements JobInstanceFactoryInterface
{
    /**
     * Create PerformantJobInterface class
     *
     * @throws \Resque\Component\Job\Exception\JobNotFoundException When the job class could not be found.
     *
     * @param JobInterface $job
     * @return PerformantJobInterface The instance of the Job that will perform.
     */
    public function createJob(JobInterface $job)
    {
        $class = $job->getJobClass();

        if (false === class_exists($class)) {
            throw new \Resque\Component\Job\Exception\JobNotFoundException(
                'Could not find job class "' . $class . '"'
            );
        }

        $instance = new $class;

        return $instance;
    }
}
