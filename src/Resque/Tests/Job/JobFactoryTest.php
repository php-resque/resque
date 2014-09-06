<?php

namespace Resque\Tests\Job;

use PHPUnit_Framework_TestCase;
use Resque\Job\JobFactory;
use Resque\Job;

class JobFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCanConstructObject()
    {
        $factory = new JobFactory();

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
        $factory = new JobFactory();

        $factory->createJob(
            new Job(
                'Resque\Tests\Jobs\NonExistent'
            )
        );
    }
}
