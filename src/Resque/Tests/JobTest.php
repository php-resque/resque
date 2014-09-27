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

    /**
     * @dataProvider dataProviderMatchFilter
     */
    public function testMatchFilter($job, $expected, $filter)
    {
        $this->assertEquals($expected, $job::matchFilter($job, $filter));
    }

    public function dataProviderMatchFilter()
    {
        $args = array(
            'baz' => 'test'
        );
        $job = new Job('FooJob', $args);

        $jobId = $job->getId();

        return array(
            array(
                $job, false,  null
            ),
            array(
                $job, false, array()
            ),
            array(
                $job, true, array('id' => $jobId)
            ),
            array(
                $job, false, array('id' => 'some-other-id')
            ),
            array(
                $job, false, array('id' => $jobId, 'class' => 'FuzzJob')
            ),
            array(
                $job, false, array('class' => 'FuzzJob')
            ),
            array(
                $job, true, array('id' => $jobId, 'class' => 'FooJob')
            ),
            array(
                $job, true, array('class' => 'FooJob')
            ),
            array(
                $job, false, array('id' => '123', 'class' => 'FooJob')
            ),
        );
    }
}
