<?php

namespace Resque\Component\Worker;

/**
 * Worker events.
 *
 * Contains all job related events thrown in the worker component.
 */
final class ResqueWorkerEvents
{
    /**
     *
     */
    const PROCESS_SIGNAL_RECEIVED = 'resque.worker.process_signal_received';

    /**
     *
     */
    const STARTED = 'resque.worker.process_started';
    const REGISTERED = 'resque.worker.registered';
    const UNREGISTERED = 'resque.worker.unregistered';

    const PROCESS_WAIT_NO_JOB = 'resque.worker.process_wait_no_job';
    const PROCESS_WAIT_PAUSED = 'resque.worker.process_wait_paused';

    const JOB_DEQUEUE_FAILED = 'resque.worker.job_dequeue_failed';
}
