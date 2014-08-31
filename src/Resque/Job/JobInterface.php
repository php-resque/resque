<?php

namespace Resque\Job;

/**
 *
 */
interface JobInterface
{
    /**
     * Perform
     *
     * @throws Job\DontPerformException when the job deoesn't want to do anything.
     *
     * @return void
     */
    public function perform();

    /**
     * @param Job $job
     * @return mixed
     */
    public function setJob(Job $job);
}
