<?php

namespace Resque\Component\Queue\Model;

/**
 * Origin queue aware interface
 *
 * A job maybe be origin queue aware, if it wants to know what queue it was dequeued from.
 */
interface OriginQueueAwareInterface
{
    /**
     * Set origin queue.
     *
     * @param QueueInterface $queue The queue the subject came from.
     * @return $this
     */
    public function setOriginQueue(QueueInterface $queue);

    /**
     * Get origin queue.
     *
     * @return QueueInterface|null The queue the subject came from, if one at all.
     */
    public function getOriginQueue();
}
