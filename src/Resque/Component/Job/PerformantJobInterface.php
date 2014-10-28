<?php

namespace Resque\Component\Job;

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
     * @param array $arguments The arguments passed in to the original enqueue call.
     *
     * @return void
     */
    public function perform($arguments);
}
