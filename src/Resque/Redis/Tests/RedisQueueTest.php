<?php

namespace Resque\Component\Core\Tests;

use Resque\Component\Core\Event\EventDispatcher;
use Resque\Redis\RedisQueue;
use Resque\Redis\Test\ResqueTestCase;
use Resque\Component\Job\Model\Job;

class RedisQueueTest extends ResqueTestCase
{
    /**
     * @var RedisQueue
     */
    protected $queue;

    public function setUp()
    {
        parent::setUp();

//        $this->queue = new RedisQueue($this->redis, new EventDispatcher());
//        $this->queue->setName('jobs');
    }

    public function testQueuedJobCanBePopped()
    {
        return $this->markTestIncomplete();

        $this->queue->push(new Job('Test_Job'));
        $this->assertSame(1, $this->queue->count());

        $job = $this->queue->pop();

        if ($job == false) {
            $this->fail('Job could not be reserved.');
        }

        $this->assertEquals('jobs', $job->getOriginQueue()->getName());
        $this->assertEquals('Test_Job', $job->getJobClass());
    }

    public function testAfterJobIsPoppedItIsRemoved()
    {
        return $this->markTestIncomplete();

        $this->queue->push(new Job('Test_Job'));
        $this->assertSame(1, $this->queue->count());
        $this->assertNotNull($this->queue->pop());
        $this->assertNull($this->queue->pop());
    }

    public function testRecreatedJobMatchesExistingJob()
    {
        return $this->markTestIncomplete();

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
        $this->assertEquals($args, $poppedJob->getArguments());
        $this->assertNull($this->queue->pop());
    }

    public function testJobRemoval()
    {
        return $this->markTestIncomplete();

        $job = new Job('JobToBeRemoved');

        $this->queue->push($job);
        $this->queue->push(new Job('JobToStay'));
        $this->assertEquals(2, $this->queue->count());

        $this->queue->remove(array('id' => $job->getId()));
        $this->assertEquals(1, $this->queue->count());

        $this->queue->remove();
        $this->assertEquals(1, $this->queue->count());
    }
}
