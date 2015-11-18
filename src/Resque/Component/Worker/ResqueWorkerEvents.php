<?php

namespace Resque\Component\Worker;

/**
 * Worker events
 *
 * Contains all job related events thrown in the worker component.
 */
final class ResqueWorkerEvents
{
    /**
     *
     * @var string
     */
    const DAEMON_SIGNAL_RECEIVED = 'resque.worker.signal_received';

    /**
     *
     * @var string
     */
    const STARTED = 'resque.worker.started';

}
