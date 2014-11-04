<?php

namespace Resque\Tests;

use Resque\Component\Core\RedisQueue;
use Resque\Component\Core\RedisStatistic;
use Resque\Component\Core\Test\ResqueTestCase;
use Resque\Component\Job\Model\Job;
use Resque\Foreman;
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
        $this->foreman->setRedisClient($this->redis);
    }

    public function testForemanRegistersWorkerInRedisSet()
    {
        $worker = new Worker(
            new RedisQueue('foo')
        );

        $this->foreman->register($worker);

        // Make sure the worker is in the list
        $this->assertCount(1, $this->redis->smembers('workers'));
        $this->assertTrue((bool)$this->redis->sismember('workers', $worker));
    }

    public function testGetAllWorkers()
    {
        $count = 3;
        // Register a few workers
        for ($i = 0; $i < $count; ++$i) {
            $queue = new RedisQueue('queue_' . $i);
            $worker = new Worker($queue);
            $this->foreman->register($worker);
        }

        // Now try to get them
        $this->assertEquals($count, count($this->foreman->all()));
    }

    public function testForemanCanDeregisterWorker()
    {
        $worker = new Worker(
            new RedisQueue('baz')
        );

        $this->foreman->register($worker);

        // Make sure the worker is in the list
        $this->assertTrue((bool)$this->redis->sismember('workers', $worker));
        $this->assertTrue($this->foreman->isRegistered($worker));

        $this->foreman->deregister($worker);

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

        $this->foreman->register($worker);

        $newWorker = $this->foreman->findWorkerById((string)$worker);
        $this->assertEquals((string)$worker, (string)$newWorker);
    }

    public function testGetWorkerByNonExistentId()
    {
        $worker = new Worker();
        $this->foreman->register($worker);

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
        $realWorker = new Worker();
        $this->foreman->register($realWorker);

        $workerId = explode(':', $realWorker);

        // Register some dead workers
        $worker = new Worker();
        $worker->setId($workerId[0] . ':1:jobs');
        $this->foreman->register($worker);

        $worker = new Worker();
        $worker->setId($workerId[0] . ':2:high,low');
        $this->foreman->register($worker);

        $this->assertCount(3, $this->foreman->all());

        $this->foreman->pruneDeadWorkers();

        // There should only be $realWorker left now
        $this->assertCount(1, $this->foreman->all());
        $this->assertTrue((bool)$this->redis->sismember('workers', $realWorker));
    }

    public function testDeadWorkerCleanUpDoesNotCleanUnknownWorkers()
    {
        // Register a dead worker on this machine
        $localWorker = new Worker();
        $workerId = explode(':', $localWorker);
        $localWorker->setId($workerId[0] . ':1:jobs');
        $this->foreman->register($localWorker);

        // Register some other false workers
        $remoteWorker = new Worker();
        $remoteWorker->setId('my.other.host:1:jobs');
        $this->foreman->register($remoteWorker);

        $this->assertCount(2, $this->foreman->all());

        $this->foreman->pruneDeadWorkers();

        // my.other.host should be left
        $workers = $this->foreman->all();
        $this->assertCount(1, $workers);
        $this->assertEquals((string)$remoteWorker, (string)$workers[0]);
        $this->assertSame('my.other.host:1:jobs', (string)$workers[0]);
    }

    public function testWorkerFailsUncompletedJobsOnDeregister()
    {
        $stats = new RedisStatistic($this->redis);
        $foreman = new Foreman();
        $foreman->setRedisClient($this->redis);

        $worker = new Worker();
        $worker->setRedisClient($this->redis);
        $worker->setStatisticsBackend($stats);

        $job = new Job('Foo');

        $worker->workingOn($job);

        $foreman->deregister($worker);

        $this->assertEquals(0, $worker->getStat('failed'));
        $this->assertEquals(1, $stats->get('failed'));
    }

    public function testForemanErasesWorkerStats()
    {
        $stats = new RedisStatistic($this->redis);

        $foreman = new Foreman();
        $foreman->setRedisClient($this->redis);
        $foreman->setStatisticsBackend($stats);

        $worker = new Worker();
        $worker->setStatisticsBackend($stats);

        $foreman->register($worker);

        $this->assertEquals(0, $worker->getStat('processed'));
        $this->assertEquals(0, $worker->getStat('failed'));

        $stats->increment('processed:' . $worker->getId(), 10);
        $stats->increment('failed:' . $worker->getId(), 5);

        $this->assertEquals(10, $worker->getStat('processed'));
        $this->assertEquals(5, $worker->getStat('failed'));

        $foreman->deregister($worker);

        $this->assertEquals(0, $worker->getStat('processed'));
        $this->assertEquals(0, $worker->getStat('failed'));
    }
}
