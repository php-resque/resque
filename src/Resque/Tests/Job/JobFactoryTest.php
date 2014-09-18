<?php

namespace Resque\Tests\Job;

use Resque\Job\JobInstanceFactory;
use Resque\Job;

class JobFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCanConstructObject()
    {
        $factory = new JobInstanceFactory();

        $instance = $factory->createJob(
            new Job(
                'Resque\Tests\Jobs\Simple'
            )
        );

        $this->assertInstanceOf('Resque\Tests\Jobs\Simple', $instance);
    }

    /**
     * @expectedException \Resque\Job\Exception\JobNotFoundException
     */
    public function testConstructionOnNonExistentClass()
    {
        $factory = new JobInstanceFactory();

        $factory->createJob(
            new Job(
                'Resque\Tests\Jobs\NonExistent'
            )
        );
    }
}
