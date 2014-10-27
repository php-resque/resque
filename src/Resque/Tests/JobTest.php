<?php

namespace Resque\Tests;

use Resque\Job;
use Resque\Job\FilterAwareJobInterface;

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

    /**
     * @dataProvider dataProviderMatchFilter
     */
    public function testMatchFilter(FilterAwareJobInterface $job, $expected, $filter)
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
