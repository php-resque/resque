<?php

namespace Resque\Tests;

use Resque\Foreman;
use Resque\Queue;
use Resque\Worker;

class ForemanTest extends ResqueTestCase
{
    /**
     * @var Foreman
     */
    protected $foreman;

    public function setUp()
    {
        parent::setUp();

        $this->foreman = new Foreman();
        $this->foreman->setRedisBackend($this->redis);
    }

    public function testForemanRegistersWorkerInRedisSet()
    {
        $worker = new Worker(
            new Queue('foo')
        );

        $this->foreman->registerWorker($worker);

        // Make sure the worker is in the list
        $this->assertCount(1, $this->redis->smembers('workers'));
        $this->assertTrue((bool)$this->redis->sismember('workers', $worker));
    }

    public function testGetAllWorkers()
    {
        $count = 3;
        // Register a few workers
        for ($i = 0; $i < $count; ++$i) {
            $queue = new Queue('queue_' . $i);
            $worker = new Worker($queue);
            $this->foreman->registerWorker($worker);
        }

        // Now try to get them
        $this->assertEquals($count, count($this->foreman->all()));
    }

    public function testForemanCanUnregisterWorker()
    {
        $worker = new Worker(
            new Queue('baz')
        );

        $this->foreman->registerWorker($worker);

        // Make sure the worker is in the list
        $this->assertTrue((bool)$this->redis->sismember('workers', $worker));
        $this->assertTrue($this->foreman->isRegistered($worker));

        $this->foreman->unregisterWorker($worker);

        $this->assertFalse($this->foreman->isRegistered($worker));
        $this->assertCount(0, $this->foreman->all());
        $this->assertCount(0, $this->redis->smembers('workers'));
    }

    public function testUnregisteredWorkerDoesNotExistInRedis()
    {
        $worker = new Worker(array());
        $this->assertFalse($this->foreman->isRegistered($worker));
    }

    public function testGetWorkerById()
    {
        $worker = new Worker();

        $this->foreman->registerWorker($worker);

        $newWorker = $this->foreman->findWorkerById((string)$worker);
        $this->assertEquals((string)$worker, (string)$newWorker);
    }

    public function testGetWorkerByNonExistentId()
    {
        $worker = new Worker();
        $this->foreman->registerWorker($worker);

        $this->assertNull($this->foreman->findWorkerById('hopefully-not-real'));
    }

    public function testForking()
    {
        $me = getmypid();

        $mockWorker = $this->getMock(
            'Resque\Worker',
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

        $this->foreman->work($workers);

        // Check the workers hold different PIDs
        foreach ($workers as $worker) {
            $this->assertNotEquals(0, $worker->pid);
            $this->assertNotEquals($me, $worker->pid);
        }
    }

    public function testWorkerCleansUpDeadWorkersOnStartup()
    {
        // Register a real worker
        $realWorker = new Worker();
        $this->foreman->registerWorker($realWorker);

        $workerId = explode(':', $realWorker);

        // Register some dead workers
        $worker = new Worker();
        $worker->setId($workerId[0] . ':1:jobs');
        $this->foreman->registerWorker($worker);

        $worker = new Worker();
        $worker->setId($workerId[0] . ':2:high,low');
        $this->foreman->registerWorker($worker);

        $this->assertEquals(3, count($this->foreman->all()));

        $this->foreman->pruneDeadWorkers();

        // There should only be $realWorker left now
        $this->assertEquals(1, count($this->foreman->all()));
        $this->assertTrue((bool)$this->redis->sismember('workers', $realWorker));
    }

    public function testDeadWorkerCleanUpDoesNotCleanUnknownWorkers()
    {
        return self::markTestSkipped();

        // Register a bad worker on this machine
        $worker = new Worker('jobs');
        $worker->setLogger(new Resque_Log());
        $workerId = explode(':', $worker);
        $worker->setId($workerId[0] . ':1:jobs');
        $worker->registerWorker();

        // Register some other false workers
        $worker = new Worker('jobs');
        $worker->setLogger(new Resque_Log());
        $worker->setId('my.other.host:1:jobs');
        $worker->registerWorker();

        $this->assertEquals(2, count(Worker::all()));

        $worker->pruneDeadWorkers();

        // my.other.host should be left
        $workers = Worker::all();
        $this->assertEquals(1, count($workers));
        $this->assertEquals((string)$worker, (string)$workers[0]);
    }
}
