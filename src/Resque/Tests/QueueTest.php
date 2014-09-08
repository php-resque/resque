<?php

namespace Resque\Tests;

use Resque\Job;
use Resque\Resque;
use Resque\Queue;
use Resque\Worker;

class QueueTest extends ResqueTestCase
{
    /**
     * @var Queue
     */
    protected $queue;

    public function setUp()
    {
        parent::setUp();

        $this->queue = new Queue('jobs');
        $this->queue->setRedisBackend($this->redis);
    }

    public function testJobCanBeEnqueued()
    {
        $this->assertTrue($this->queue->push(new Job('Test_Job')));
    }

    public function testQueuedJobCanBePopped()
    {
        $this->queue->push(new Job('Test_Job'));
        $this->assertSame(1, $this->queue->size());

        $job = $this->queue->pop();

        if ($job == false) {
            $this->fail('Job could not be reserved.');
        }

        $this->assertEquals('jobs', $job->queue->getName());
        $this->assertEquals('Test_Job', $job->getJobClass());
    }

    public function testAfterJobIsPoppedItIsRemoved()
    {
        $this->queue->push(new Job('Test_Job'));
        $this->assertSame(1, $this->queue->size());
        $this->assertNotNull($this->queue->pop());
        $this->assertNull($this->queue->pop());
    }

    public function testAllReturnsRegisteredQueues()
    {
        $queues = $this->queue->all();
        $this->assertCount(0, $queues);

        $foo = new Queue('foo');
        $foo->setRedisBackend($this->redis);
        $foo->register();

        $queues = $this->queue->all();
        $this->assertCount(1, $queues);
        $this->assertEquals('foo', (string)$queues[0]);

        $bar = new Queue('bar');
        $bar->setRedisBackend($this->redis);
        $bar->register();

        $queues = $this->queue->all();
        $this->assertCount(2, $queues);
        $this->assertNotContains($foo, $queues);
        $this->assertNotContains($bar, $queues);
    }
}
