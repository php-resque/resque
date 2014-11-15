<?php

namespace Resque\Component\Worker\Registry;

use Resque\Component\Worker\Model\WorkerInterface;

interface WorkerRegistryInterface
{
    /**
     * Registers the given worker in storage
     *
     * @param WorkerInterface $worker
     *
     * @return $this
     */
    public function register(WorkerInterface $worker);

    /**
     * Is the given worker already registered?
     *
     * @param WorkerInterface $worker
     *
     * @return bool True if the worker is already registered.
     */
    public function isRegistered(WorkerInterface $worker);

    /**
     * Removes the given worker and it's jobs with in it
     *
     * @param WorkerInterface $worker
     *
     * @return $this
     */
    public function deregister(WorkerInterface $worker);

    /**
     * Return all registered workers
     *
     * @return WorkerInterface[]
     */
    public function all();

    /**
     * @param $workerId
     * @return WorkerInterface|null
     */
    public function findWorkerById($workerId);
}
