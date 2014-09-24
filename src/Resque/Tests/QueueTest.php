<?php

namespace Resque\Tests;

use Resque\Job;
use Resque\Queue;

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

    public function testNameIsSet()
    {
        $this->assertSame('jobs', $this->queue->getName());
    }

    public function testUnregister()
    {
        $this->queue->register();
        $this->assertTrue($this->redis->sismember('queues', 'jobs'));
        $this->assertEquals(0, $this->queue->unregister());
        $this->assertFalse($this->redis->exists('queue:jobs'));
    }

    public function testUnregisterWithQueuedJobs()
    {
        $this->queue->push(new Job('Foo'));
        $this->queue->push(new Job('Foo'));

        $this->assertEquals(2, $this->queue->size());
        $this->assertEquals(2, $this->queue->unregister());
        $this->assertEquals(0, $this->queue->size());
        $this->assertFalse($this->redis->exists('queue:jobs'));
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
        $this->assertEquals('foo', (string)$queues['foo']);

        $bar = new Queue('bar');
        $bar->setRedisBackend($this->redis);
        $bar->register();

        $queues = $this->queue->all();
        $this->assertCount(2, $queues);
        $this->assertNotContains($foo, $queues);
        $this->assertNotContains($bar, $queues);
    }

    public function testEnqueuedJobReturnsExactSamePassedInArguments()
    {
        $args = array(
            'int' => 123,
            'numArray' => array(
                1,
                2,
            ),
            'assocArray' => array(
                'key1' => 'value1',
                'key2' => 'value2'
            ),
        );

        $this->queue->push(
            new Job(
                'Test_Job',
                $args
            )
        );

        $job = $this->queue->pop();

        $this->assertNotNull($job);
        $this->assertEquals($args, $job->getArguments());
    }

    public function testRecreatedJobMatchesExistingJob()
    {
        $args = array(
            'int' => 123,
            'numArray' => array(
                1,
                2,
            ),
            'assocArray' => array(
                'key1' => 'value1',
                'key2' => 'value2'
            ),
        );

        $pushedJob = new Job(
            'Test_Job',
            $args
        );

        $this->queue->push($pushedJob);

        $poppedJob = $this->queue->pop();

        $this->assertNotNull($poppedJob);
        $this->assertEquals($pushedJob->getId(), $poppedJob->getId());
        $this->assertEquals($pushedJob->getJobClass(), $poppedJob->getJobClass());
        $this->assertEquals($pushedJob->getArguments(), $poppedJob->getArguments());
        $this->assertNull($this->queue->pop());
    }
}
