<?php

namespace Resque\Tests;

use Resque\Foreman;
use Resque\Queue;
use Resque\Worker;

class ForemanTest extends ResqueTestCase
{
    public function testForemanRegistersWorkerInRedisSet()
    {
        $worker = new Worker(
            new Queue('foo')
        );

        $foreman = new Foreman();

        $foreman
            ->addWorker($worker);

        // Make sure the worker is in the list
        $this->assertTrue((bool)$this->redis->sismember('resque:workers', $worker));
    }

    public function testGetAllWorkers()
    {
        $foreman = new Foreman();
        $count = 3;

        // Register a few workers
        for ($i = 0; $i < $count; ++$i) {
            $queue = new Queue('queue_' . $i);
            $worker = new Worker($queue);
            $foreman->addWorker($worker);
        }

        // Now try to get them
        $this->assertEquals($count, count($foreman->all()));
    }

    public function testForemanCanUnregisterWorker()
    {
        $worker = new Worker(
            new Queue('baz')
        );

        $foreman = new Foreman();

        $foreman
            ->registerWorker($worker);

        // Make sure the worker is in the list
        $this->assertTrue((bool)$this->redis->sismember('resque:workers', $worker));
        $this->assertTrue($foreman->isRegistered($worker));

        $foreman
            ->unregisterWorker($worker);

        $this->assertFalse($foreman->isRegistered($worker));
        $this->assertCount(0, $foreman->all());
        $this->assertCount(0, $this->redis->smembers('resque:workers'));
    }

    public function testInvalidWorkerDoesNotExist()
    {
        $foreman = new Foreman();

        $worker = new Worker(array());

        $this->assertFalse($foreman->isRegistered($worker));
    }

    public function testForking()
    {
        $me = getmygid();
        $workMethodCalls = 0;

        $mockWorker = $this->getMock(
            'Resque\Worker',
            array('work'),
            array(array())
        );

        $mockWorker
            ->expects($this->any())
            ->method('work')
            ->will(
                $this->returnCallback(function () use (&$workMethodCalls) {
                    $workMethodCalls++;
                })
            );

        $foreman = new Foreman();
        $foreman
            ->addWorker(clone $mockWorker)
            ->addWorker(clone $mockWorker)
            ->addWorker(clone $mockWorker)
            ->addWorker(clone $mockWorker)
            ->addWorker(clone $mockWorker);

        $foreman
            ->work();

        $this->assertSame(0, $workMethodCalls);
        $this->assertCount(5, $foreman->allLocal());
        foreach ($foreman->allLocal() as $worker) {
            $this->assertNotEquals(0, $worker->pid);
            $this->assertNotEquals($me, $worker->pid);
        }
    }

    public function testWorkerCleansUpDeadWorkersOnStartup()
    {
        return $this->markTestIncomplete();

        // Register a good worker
        $goodWorker = new Worker('jobs');
        $goodWorker->setLogger(new Resque_Log());
        $goodWorker->registerWorker();
        $workerId = explode(':', $goodWorker);

        // Register some bad workers
        $worker = new Worker('jobs');
        $worker->setLogger(new Resque_Log());
        $worker->setId($workerId[0] . ':1:jobs');
        $worker->registerWorker();

        $worker = new Worker(array('high', 'low'));
        $worker->setLogger(new Resque_Log());
        $worker->setId($workerId[0] . ':2:high,low');
        $worker->registerWorker();

        $this->assertEquals(3, count(Worker::all()));

        $goodWorker->pruneDeadWorkers();

        // There should only be $goodWorker left now
        $this->assertEquals(1, count(Worker::all()));
    }

    public function testDeadWorkerCleanUpDoesNotCleanUnknownWorkers()
    {
        return $this->markTestIncomplete();

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
