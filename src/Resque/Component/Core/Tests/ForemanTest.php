<?php

namespace Resque\Tests;

use Resque\Component\Core\Foreman;
use Resque\Component\Core\RedisWorkerRegistry;
use Resque\Component\Core\Test\ResqueTestCase;

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

    public function setUp()
    {
        parent::setUp();

        $this->workerRegistry = new RedisWorkerRegistry($this->redis);
        $this->foreman = new Foreman($this->workerRegistry);
    }

    public function testForking()
    {
        return $this->markTestIncomplete('this is failing, as I need to restore disconnecting from redis on pre-fork');

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

    public function testWorkerCleansUpDeadWorkersOnStartup()
    {
        // Register a real worker
        $realWorker = $this->getMock('Resque\Component\Worker\Model\WorkerInterface');
        $realWorker->expects($this->atLeastOnce())->method('getId')->will($this->returnValue('localhost:1:jobs'));
        $this->workerRegistry->register($realWorker);

        $workerId = explode(':', $realWorker->getId());

        // Register some dead workers
        $worker = $this->getMock('Resque\Component\Worker\Model\WorkerInterface');
        $worker->expects($this->atLeastOnce())->method('getId')->will($this->returnValue($workerId[0] . ':1:jobs'));
        $this->workerRegistry->register($worker);

        $worker = $this->getMock('Resque\Component\Worker\Model\WorkerInterface');
        $worker->expects($this->atLeastOnce())->method('getId')->will($this->returnValue($workerId[0] . ':2:high,low'));
        $this->workerRegistry->register($worker);

        $this->assertCount(3, $this->workerRegistry->count());

        $this->foreman->pruneDeadWorkers();

        // There should only be $realWorker left now
        $this->assertCount(1, $this->workerRegistry->all());
        $this->assertTrue((bool)$this->redis->sismember('workers', $realWorker));
    }

    public function testDeadWorkerCleanUpDoesNotCleanUnknownWorkers()
    {
        // Register a dead worker on this machine
        $localWorker = $this->getMock('Resque\Component\Worker\Model\WorkerInterface');
        $workerId = explode(':', $localWorker);
        $localWorker->setId($workerId[0] . ':1:jobs');
        $this->workerRegistry->register($localWorker);

        // Register some other false workers
        $remoteWorker = $this->getMock('Resque\Component\Worker\Model\WorkerInterface');
        $remoteWorker->setId('my.other.host:1:jobs');
        $this->workerRegistry->register($remoteWorker);

        $this->assertCount(2, $this->workerRegistry->all());

        $this->foreman->pruneDeadWorkers();

        // my.other.host should be left
        $workers = $this->workerRegistry->all();
        $this->assertCount(1, $workers);
        $this->assertEquals((string)$remoteWorker, (string)$workers[0]);
        $this->assertSame('my.other.host:1:jobs', (string)$workers[0]);
    }
}
