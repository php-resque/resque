<?php

namespace Resque\Component\Queue\Registry;

use Resque\Component\Queue\Model\QueueInterface;

interface QueueRegistryAdapterInterface
{
    /**
     * Save queue.
     *
     * @param QueueInterface $queue
     * @return $this
     */
    public function save(QueueInterface $queue);

    /**
     * Has queue?
     *
     * @param QueueInterface $queue
     * @return bool
     */
    public function has(QueueInterface $queue);

    /**
     * Delete queue.
     *
     * @param QueueInterface $queue
     * @return integer The number of jobs removed.
     */
    public function delete(QueueInterface $queue);

    /**
     * All queues.
     *
     * @return string[] A list of registered queues.
     */
    public function all();
}
