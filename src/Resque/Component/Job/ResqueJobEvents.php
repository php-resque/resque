<?php

namespace Resque\Component\Job;

/**
 * Contains all job related events thrown in the worker component
 */
final class ResqueJobEvents
{
    /**
     * The STATE_CHANGE event is thrown each time a Model\TrackableJobInterface
     * has it's state changed by a worker.
     *
     * The event listener receives a Resque\Component\Job\Event\JobEvent instance.
     *
     * @var string
     */
    const STATE_CHANGE = 'resque.job.state_change';
}
