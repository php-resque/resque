<?php

namespace Resque\Component\Queue\Event;

use Resque\Component\Queue\Model\QueueInterface;

class QueueEvent
{
    /**
     * @var QueueInterface The queue related to this event.
     */
    protected $queue;

    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @return QueueInterface
     */
    public function getQueue()
    {
        return $this->queue;
    }
}
