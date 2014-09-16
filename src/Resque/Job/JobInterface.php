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
     * @return void
     */
    public function perform();
}
