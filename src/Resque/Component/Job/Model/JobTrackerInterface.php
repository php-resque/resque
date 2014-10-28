<?php

namespace Resque\Component\Job\Model;

/**
 * Job status tracker
 */
interface JobTrackerInterface
{
    /**
     * Check if we're actually checking the status of the loaded job status
     * instance.
     *
     * @return boolean True if the status is being monitored, false if not.
     */
    public function isTracking(JobInterface $job);

    /**
     * Track the current state of the given TrackableJobInterface
     */
    public function track(TrackableJobInterface $job);

    /**
     * Fetch the status for the job being monitored.
     *
     * @return mixed null if the state is not being monitored, otherwise the last known state of the job.
     */
    public function get(JobInterface $job);

    /**
     * Stop tracking the state of a JobInterface
     */
    public function stop(JobInterface $job);
}
