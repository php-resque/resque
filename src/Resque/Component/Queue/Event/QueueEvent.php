<?php

namespace Resque\Component\Queue\Event;

use Resque\Component\Queue\Model\QueueInterface;

class QueueEvent
{
    /**
     * @var QueueInterface The queue, that is the subject of this event.
     */
    protected $queue;

    /**
     * Constructor
     *
     * @param QueueInterface $queue The subject queue.
     */
    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    /**
     * Get queue.
     *
     * @return QueueInterface the subject queue.
     */
    public function getQueue()
    {
        return $this->queue;
    }
}
