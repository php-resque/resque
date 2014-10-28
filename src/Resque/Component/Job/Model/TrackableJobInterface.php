<?php

namespace Resque\Component\Job\Model;

interface TrackableJobInterface extends JobInterface
{
    /**
     * Get job state
     *
     * @return string
     */
    public function getState();

    /**
     * Set job state
     *
     * @param string $state
     */
    public function setState($state);
}
