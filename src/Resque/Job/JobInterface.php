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
     * @throws Job\DontPerformException when the job does not want to do anything.
     *
     * @return void
     */
    public function perform();
}
