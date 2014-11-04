<?php

namespace Resque\Component\Worker;

/**
 * Contains all job related events thrown in the worker component
 */
final class ResqueWorkerEvents
{
    /*
     * The event listener receives a Resque\Component\Worker\Event\WorkerEvent instance.
     *
     * @var string
     */
    const START_UP = 'resque.worker.start_up';

    /*
     * The event listener receives a Resque\Component\Worker\Event\WorkerJobEvent instance.
     *
     * @var string
     */
    const BEFORE_FORK_TO_PERFORM = 'resque.worker.before_fork_to_perform';

    /*
     * The event listener receives a Resque\Component\Worker\Event\WorkerJobEvent instance.
     *
     * @var string
     */
    const AFTER_FORK_TO_PERFORM = 'resque.worker.after_fork_to_perform';
}
