<?php

namespace Resque\Component\Queue\Model;

/**
 * Job queue aware interface
 */
interface OriginQueueAwareInterface
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
