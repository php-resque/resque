<?php

namespace Resque\Component\Worker;

/**
 * Worker events
 *
 * Contains all job related events thrown in the worker component
 */
final class ResqueWorkerEvents
{
    /**
     * The event listener receives a Resque\Component\Worker\Event\WorkerEvent instance.
     *
     * @var string
     */
    const REGISTERED = 'resque.worker.registered';

    /**
     * The event listener receives a Resque\Component\Worker\Event\WorkerEvent instance.
     *
     * @var string
     */
    const UNREGISTERED = 'resque.worker.unregistered';

    /**
     * The event listener receives a Resque\Component\Worker\Event\WorkerEvent instance.
     *
     * @var string
     */
    const PERSISTED = 'resque.worker.persisted';

    /**
     * The event listener receives a Resque\Component\Worker\Event\WorkerEvent instance.
     *
     * @var string
     */
    const START_UP = 'resque.worker.started';

    /**
     * The event listener receives a Resque\Component\Worker\Event\WorkerEvent instance.
     *
     * @var string
     */
    const SHUTDOWN = 'resque.worker.shutdown';

    /**
     * The event listener receives a Resque\Component\Worker\Event\WorkerJobEvent instance.
     *
     * @var string
     */
    const BEFORE_FORK_TO_PERFORM = 'resque.worker.before_fork_to_perform';

    /**
     * The event listener receives a Resque\Component\Worker\Event\WorkerJobEvent instance.
     *
     * @var string
     */
    const AFTER_FORK_TO_PERFORM = 'resque.worker.after_fork_to_perform';

    /**
     *
     */
    const WAIT_NO_JOB = 'resque.worker.wait_no_job';
}
