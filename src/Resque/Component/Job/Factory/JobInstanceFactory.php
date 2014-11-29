<?php

namespace Resque\Component\Job\Factory;

use Resque\Component\Job\Exception\JobNotFoundException;
use Resque\Component\Job\Model\JobInterface;

class JobInstanceFactory implements JobInstanceFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createPerformantJob(JobInterface $job)
    {
        $class = $job->getJobClass();

        if (false === class_exists($class)) {
            throw new JobNotFoundException(
                'Could not find job class "' . $class . '"'
            );
        }

        $instance = new $class;

        return $instance;
    }
}
