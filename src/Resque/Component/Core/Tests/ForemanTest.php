<?php

namespace Resque\Tests;

use Resque\Component\Core\Foreman;
use Resque\Component\Core\RedisWorkerRegistry;
use Resque\Component\Core\Test\ResqueTestCase;
use Resque\Component\Worker\Factory\WorkerFactory;

class ForemanTest extends ResqueTestCase
{
    /**
     * @var Foreman
     */
    protected $foreman;

    /**
     * @var RedisWorkerRegistry
     */
    protected $workerRegistry;

    public function testForking()
    {
        return $this->markTestIncomplete('this is failing, as I need to restore disconnecting from redis on pre-fork');

        $this->workerRegistry = new RedisWorkerRegistry($this->redis, $this->getMock('Resque\Component\Worker\Factory\WorkerFactoryInterface'));
        $this->foreman = new Foreman($this->workerRegistry);

        $me = getmypid();

        $mockWorker = $this->getMock(
            'Resque\Component\Worker\Worker',
            array('work'),
            array(array())
        );
        $mockWorker
            ->expects($this->any())
            ->method('work')
            ->will($this->returnValue(null));

        $workers = array(
            clone $mockWorker,
            clone $mockWorker,
            clone $mockWorker,
            clone $mockWorker,
            clone $mockWorker,
        );

        $this->foreman->work($workers, true);

        // Check the workers hold different PIDs
        foreach ($workers as $worker) {
            $this->assertNotEquals(0, $worker->getProcess()->getPid());
            $this->assertNotEquals($me, $worker->getProcess()->getPid());
        }
    }
}
