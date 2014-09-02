<?php

namespace Resque\Tests;

use Resque\Job;
use Resque\Resque;
use Resque\Foreman;
use Resque\Queue;
use Resque\Worker;

class JobTest extends ResqueTestCase
{
    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var Worker
     */
    protected $worker;

    public function setUp()
    {
        parent::setUp();

        $this->queue = new Queue('jobs');
        $this->worker = new Worker($this->queue);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testObjectArgumentsCannotBePassedToJob()
    {
        $args = new \stdClass;
        $args->test = 'somevalue';

        new Job('Test_Job', $args);
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

        $insertedJob = new Job(
            'Test_Job',
            $args
        );

        $this->queue->enqueue($insertedJob);

        $poppedJob = $this->queue->pop();

        $this->assertEquals($insertedJob->getJobClass(), $poppedJob->getJobClass());
        $this->assertEquals($insertedJob->getArguments(), $poppedJob->getArguments());
        $this->assertNull($this->queue->pop());
    }

    public function testFailedJobExceptionsAreCaught()
    {
        return self::markTestSkipped();


        $payload = array(
            'class' => 'Failing_Job',
            'args' => null
        );
        $job = new Resque_Job('jobs', $payload);
        $job->worker = $this->worker;

        $this->worker->perform($job);

        $this->assertEquals(1, Resque_Stat::get('failed'));
        $this->assertEquals(1, Resque_Stat::get('failed:' . $this->worker));
    }

    /**
     * @expectedException Resque_Exception
     */
    public function testJobWithoutPerformMethodThrowsException()
    {
        return self::markTestSkipped();


        Resque::enqueue('jobs', 'Test_Job_Without_Perform_Method');
        $job = $this->worker->reserve();
        $job->worker = $this->worker;
        $job->perform();
    }

    /**
     * @expectedException Resque_Exception
     */
    public function testInvalidJobThrowsException()
    {
        return self::markTestSkipped();


        Resque::enqueue('jobs', 'Invalid_Job');
        $job = $this->worker->reserve();
        $job->worker = $this->worker;
        $job->perform();
    }

    public function testJobWithSetUpCallbackFiresSetUp()
    {
        return self::markTestSkipped();


        $payload = array(
            'class' => 'Test_Job_With_SetUp',
            'args' => array(
                'somevar',
                'somevar2',
            ),
        );
        $job = new Resque_Job('jobs', $payload);
        $job->perform();

        $this->assertTrue(Test_Job_With_SetUp::$called);
    }

    public function testJobWithTearDownCallbackFiresTearDown()
    {
        return self::markTestSkipped();


        $payload = array(
            'class' => 'Test_Job_With_TearDown',
            'args' => array(
                'somevar',
                'somevar2',
            ),
        );
        $job = new Resque_Job('jobs', $payload);
        $job->perform();

        $this->assertTrue(Test_Job_With_TearDown::$called);
    }

    public function testJobWithNamespace()
    {
        return self::markTestSkipped();


        Resque_Redis::prefix('php');
        $queue = 'jobs';
        $payload = array('another_value');
        Resque::enqueue($queue, 'Test_Job_With_TearDown', $payload);

        $this->assertEquals(Resque::queues(), array('jobs'));
        $this->assertEquals(Resque::size($queue), 1);

        Resque_Redis::prefix('resque');
        $this->assertEquals(Resque::size($queue), 0);
    }
}
