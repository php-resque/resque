<?php

namespace Resque\Component\Job;

/**
 * Performant job
 *
 * Job target classes must implement this, else a worker cannot ask them to perform.
 */
interface PerformantJobInterface
{
    /**
     * Perform.
     *
     * This is how your task is invoked.
     *
     * A clean exit is what considers the job as complete. Be mindful of this with your exception
     * handling and exit codes.
     *
     * @param array $arguments The arguments passed in to the original enqueue call.
     *
     * @return void
     */
    public function perform($arguments);
}
