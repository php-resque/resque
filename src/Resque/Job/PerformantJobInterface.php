<?php

namespace Resque\Job;

/**
 * Performant Job Interface
 *
 * Job classes must implement this, else a worker cannot ask them to perform.
 */
interface PerformantJobInterface
{
    /**
     * Perform
     *
     * This is how your task is invoked.
     *
     * @return void
     */
    public function perform();
}
