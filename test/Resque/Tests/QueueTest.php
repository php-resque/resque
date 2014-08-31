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
    }

    public function testJobCanBeEnqueued()
    {
        $this->assertTrue((bool)$this->queue->enqueue(new Job('Test_Job')));
    }

    public function testQueuedJobCanBePopped()
    {
        $this->queue->enqueue(new Job('Test_Job'));

        $job = $this->queue->pop();

        if ($job == false) {
            $this->fail('Job could not be reserved.');
        }

        $this->assertEquals('jobs', $job->queue->getName());
        $this->assertEquals('Test_Job', $job->getJobClass());
    }

    public function testAfterJobIsPoppedItIsRemoved()
    {
        $this->queue->enqueue(new Job('Test_Job'));
        $this->queue->pop();
        $this->assertNull($this->queue->pop());
    }
}
