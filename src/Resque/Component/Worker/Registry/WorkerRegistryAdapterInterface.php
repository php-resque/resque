<?php

namespace Resque\Component\Worker\Registry;

use Resque\Component\Worker\Model\WorkerInterface;

interface WorkerRegistryAdapterInterface
{
    /**
     * Save worker.
     *
     * @param WorkerInterface $worker The worker to save to database.
     * @return void
     */
    public function save(WorkerInterface $worker);

    /**
     * Has queue?
     *
     * @param WorkerInterface $worker
     * @return bool
     */
    public function has(WorkerInterface $worker);

    /**
     * Delete queue.
     *
     * @param WorkerInterface $worker
     * @return integer The number of jobs removed.
     */
    public function delete(WorkerInterface $worker);

    /**
     * Get all workers.
     *
     * @return array
     */
    public function all();

    /**
     * Count.
     *
     * @return int The number of registered workers.
     */
    public function count();
}
