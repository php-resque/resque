<?php

namespace Resque\Component\Core\Tests;

use Resque\Component\Core\Event\EventDispatcher;
use Resque\Component\Core\RedisQueue;
use Resque\Component\Core\RedisQueueRegistry;
use Resque\Component\Core\Test\ResqueTestCase;
use Resque\Component\Job\Model\Job;

class RedisQueueRegistryTest extends ResqueTestCase
{
    /**
     * @var \Resque\Component\Queue\Registry\QueueRegistryInterface
     */
    protected $registry;

    /**
     * @var RedisQueue
     */
    protected $queue;

    public function setUp()
    {
        parent::setUp();

        $this->registry = new RedisQueueRegistry($this->redis, new EventDispatcher());
        $this->queue = $this->registry->createQueue('jobs');
    }

    public function testDeregister()
    {
        $this->registry->register($this->queue);
        $this->assertTrue($this->redis->sismember('queues', 'jobs'));
        $this->assertEquals(0, $this->registry->deregister($this->queue));
        $this->assertFalse($this->redis->exists('queue:jobs'));
    }

    public function testDeregisterWithQueuedJobs()
    {
        $this->queue->push(new Job('Foo'));
        $this->queue->push(new Job('Foo'));

        $this->assertEquals(2, $this->queue->count());
        $this->assertEquals(2, $this->registry->deregister($this->queue));
        $this->assertEquals(0, $this->queue->count());
        $this->assertFalse($this->redis->exists('queue:jobs'));
    }

    public function testAllReturnsRegisteredQueues()
    {
        $queues = $this->registry->all();
        $this->assertCount(0, $queues);

        $foo = $this->registry->createQueue('foo');
        $this->registry->register($foo);

        $queues = $this->registry->all();
        $this->assertCount(1, $queues);
        $this->assertEquals('foo', $queues['foo']);


        $bar = $this->registry->createQueue('bar');
        $this->registry->register($bar);

        $queues = $this->registry->all();
        $this->assertCount(2, $queues);
        $this->assertNotContains($foo, $queues);
        $this->assertNotContains($bar, $queues);
    }
}
