<?php

namespace Resque\Component\Job\Model;

/**
 * Job status tracker
 */
interface JobTrackerInterface
{
    /**
     * Is tracked?
     *
     * Checks if we're actually tracking the status of the given job.
     *
     * @return boolean True if the status is being monitored, false if not.
     */
    public function isTracking(JobInterface $job);

    /**
     * Is completed?
     *
     * @param JobInterface $job
     * @return boolean True job has been tracked, and it's status a completed one.
     */
    public function isComplete(JobInterface $job);

    /**
     * Track the current state of the given TrackableJobInterface
     */
    public function track(TrackableJobInterface $job);

    /**
     * Fetch the status for the job being monitored.
     *
     * @param JobInterface $job
     * @return mixed null if the state is not being monitored, otherwise the last known state of the job.
     */
    public function get(JobInterface $job);

    /**
     * Stop tracking the state of a JobInterface
     * @param JobInterface $job
     */
    public function stop(JobInterface $job);
}
