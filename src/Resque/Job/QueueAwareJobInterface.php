<?php

namespace Resque\Job;

use Resque\QueueInterface;

/**
 * Queue aware job interface
 */
interface QueueAwareJobInterface
{
    /**
     * Set origin Queue
     *
     * @param QueueInterface $queue
     * @return $this
     */
    public function setOriginQueue(QueueInterface $queue);

    /**
     * Returns origin QueueInterface, if it has been set.
     *
     * @return QueueInterface|null
     */
    public function getOriginQueue();
}
