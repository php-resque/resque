<?php

namespace Resque\Component\Queue\Model;

/**
 * Abstract Queue
 */
abstract class AbstractQueue implements QueueInterface
{
    /**
     * @var string The name of the queue.
     */
    protected $name;

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }
}
