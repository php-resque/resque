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
     * Count
     *
     * @return int The number of registered workers.
     */
    public function count();

    /**
     * Find worker
     *
     * Given a worker Id, find it and return an instantiated worker class for it.
     *
     * @param string $id The ID of the worker.
     * @return WorkerInterface|null Instance of the worker. null if the worker does not exist.
     */
    public function findWorkerById($id);

    /**
     * Save worker state
     *
     * @todo I'm not 100% sure about this being here. Think about it.
     *
     * @param WorkerInterface $worker The worker persist state to the registry. It must be registered.
     * @return $this
     */
    public function persist(WorkerInterface $worker);
}
