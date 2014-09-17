<?php

namespace Resque\Job;

/**
 * Job Interface
 *
 * Job classes must implement this.
 */
interface JobInterface
{
    /**
     * Perform
     *
     * This is where your background task runs.
     *
     * @return void
     */
    public function perform();
}
