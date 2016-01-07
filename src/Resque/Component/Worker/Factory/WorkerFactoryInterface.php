<?php

namespace Resque\Component\Worker\Factory;

use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\WorkerProcessInterface;

interface WorkerFactoryInterface
{
    /**
     * Create worker.
     *
     * @return WorkerInterface
     */
    public function createWorker();

    /**
     * Create worker from ID.
     *
     * @param $workerId
     * @return WorkerInterface
     */
    public function createWorkerFromId($workerId);

    /**
     * Create worker process.
     *
     * @param WorkerInterface $worker
     * @return WorkerProcessInterface
     */
    public function createWorkerProcess(WorkerInterface $worker);
}
