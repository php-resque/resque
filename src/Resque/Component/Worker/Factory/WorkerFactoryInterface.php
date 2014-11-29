<?php

namespace Resque\Component\Worker\Factory;

use Resque\Component\Worker\Model\WorkerInterface;

interface WorkerFactoryInterface
{
    /**
     * Create worker
     *
     * @return WorkerInterface
     */
    public function createWorker();
}
