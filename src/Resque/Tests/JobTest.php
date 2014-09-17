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
     * @expectedException \InvalidArgumentException
     */
    public function testObjectArgumentsCannotBePassedToJob()
    {
        $args = new \stdClass;
        $args->test = 'somevalue';

        new Job('Test_Job', $args);
    }

    public function testHoldsId()
    {
        $job = new Job('bar');
        $id = $job->getId();
        $this->assertNotNull($id);
        $this->assertEquals($id, $job->getId());
    }

    public function testCloneDropsId()
    {
        $job = new Job(
            'foo',
            array('arg' => 'bar')
        );
        $this->assertNotNull($job->getId());
        $clone = clone $job;
        $this->assertNotSame($job->getId(), $clone->getId());
        $this->assertNotNull($clone->getId());
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
     * @expectedException \Resque\Exception\ResqueException
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
     * @expectedException \Resque\Exception\ResqueException
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
