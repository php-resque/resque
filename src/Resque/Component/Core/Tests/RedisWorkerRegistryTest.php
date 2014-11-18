<?php

namespace Resque\Tests;

use Resque\Component\Core\RedisStatistic;
use Resque\Component\Core\RedisWorkerRegistry;
use Resque\Component\Core\Test\ResqueTestCase;
use Resque\Component\Job\Model\Job;
use Resque\Component\Worker\Worker;

class RedisWorkerRegistryTest extends ResqueTestCase
{
    /**
     * @var RedisWorkerRegistry
     */
    protected $workerRegistry;

    public function setUp()
    {
        parent::setUp();

        $this->workerRegistry = new RedisWorkerRegistry($this->redis);
    }

    public function testRegistersWorkerInRedisSet()
    {
        $worker = new Worker();

        $this->workerRegistry->register($worker);

        // Make sure the worker is in the list
        $this->assertCount(1, $this->redis->smembers('workers'));
        $this->assertTrue((bool)$this->redis->sismember('workers', $worker));
    }

    public function testGetAllWorkers()
    {
        $count = 3;
        // Register a few workers
        for ($i = 0; $i < $count; ++$i) {
            $worker = new Worker();
            $worker->setId($i);
            $this->workerRegistry->register($worker);
        }

        // Now try to get them
        $this->assertEquals($count, count($this->workerRegistry->all()));
    }

    public function testCanDeregisterWorker()
    {
        $worker = new Worker();

        $this->workerRegistry->register($worker);

        // Make sure the worker is in the list
        $this->assertTrue((bool)$this->redis->sismember('workers', $worker));
        $this->assertTrue($this->workerRegistry->isRegistered($worker));

        $this->workerRegistry->deregister($worker);

        $this->assertFalse($this->workerRegistry->isRegistered($worker));
        $this->assertCount(0, $this->workerRegistry->all());
        $this->assertCount(0, $this->redis->smembers('workers'));
    }

    public function testUnregisteredWorkerDoesNotExistInRedis()
    {
        $worker = new Worker(array());
        $this->assertFalse($this->workerRegistry->isRegistered($worker));
    }

    public function testGetWorkerById()
    {
        $worker = new Worker();

        $this->workerRegistry->register($worker);

        $newWorker = $this->workerRegistry->findWorkerById((string)$worker);
        $this->assertEquals((string)$worker, (string)$newWorker);
    }

    public function testGetWorkerByNonExistentId()
    {
        $worker = new Worker();
        $this->workerRegistry->register($worker);

        $this->assertNull($this->workerRegistry->findWorkerById('hopefully-not-real'));
    }

    public function testDeregisterErasesWorkerStats()
    {
        $stats = new RedisStatistic($this->redis);

        $worker = new Worker();
        $worker->setStatisticsBackend($stats);

        $this->workerRegistry->register($worker);

        $this->assertEquals(0, $worker->getStat('processed'));
        $this->assertEquals(0, $worker->getStat('failed'));

        $stats->increment('processed:' . $worker->getId(), 10);
        $stats->increment('failed:' . $worker->getId(), 5);

        $this->assertEquals(10, $worker->getStat('processed'));
        $this->assertEquals(5, $worker->getStat('failed'));

        $this->workerRegistry->deregister($worker);

        $this->assertEquals(0, $worker->getStat('processed'));
        $this->assertEquals(0, $worker->getStat('failed'));
    }

    public function testWorkerFailsUncompletedJobsOnDeregister()
    {
        $stats = new RedisStatistic($this->redis);

        $worker = new Worker();
        $worker->setRedisClient($this->redis);
        $worker->setStatisticsBackend($stats);

        $job = new Job('Foo');

        $worker->workingOn($job);

        $this->workerRegistry->deregister($worker);

        $this->assertEquals(0, $worker->getStat('failed'));
        $this->assertEquals(1, $stats->get('failed'));
    }
}
