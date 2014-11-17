<?php

namespace Resque\Component\Job;

/**
 * Job Events
 *
 * Contains all job related events
 */
final class ResqueJobEvents
{
    /**
     * The STATE_CHANGE event is dispatched each time a Model\TrackableJobInterface
     * has it's state changed by a worker.
     *
     * The event listener receives a JobEvent instance.
     *
     * @see \Resque\Component\Job\Event\JobEvent
     *
     * @var string
     */
    const STATE_CHANGE = 'resque.job.state_change';

    /**
     * The BEFORE_PERFORM event is dispatched before a job perform is attempted.
     *
     * The event listener receives a JobInstanceEvent instance.
     *
     * @see \Resque\Component\Job\Event\JobInstanceEvent
     *
     * @var string
     */
    const BEFORE_PERFORM = 'resque.job.before_perform';

    /**
     * The PERFORMED event is dispatched whenever a job successfully performs from with in a worker.
     *
     * The event listener receives a JobEvent instance.
     *
     * @see \Resque\Component\Job\Event\JobEvent
     *
     * @var string
     */
    const PERFORMED = 'resque.job.performed';

    /**
     * The FAILED event is dispatched whenever a job fails to perform with in a worker. The cause may be from
     * a worker child dirty exit, or an uncaught exception from with in the job itself.
     *
     * The event listener receives a JobFailedEvent instance.
     *
     * @see \Resque\Component\Job\Event\JobFailedEvent
     *
     * @var string
     */
    const FAILED = 'resque.job.failed';
}
