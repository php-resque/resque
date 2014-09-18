<?php

namespace Resque\Tests;

use Resque\Job;
use Resque\QueueWildcard;

class QueueWildcardTest extends ResqueTestCase
{
    /**
     * @var QueueWildcard
     */
    protected $wildcard;

    public function setUp()
    {
        parent::setUp();

        $this->wildcard = new QueueWildcard();
        $this->wildcard->setRedisBackend($this->redis);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Wildcard queue does not support pushing
     */
    public function testJobCannotBeEnqueued()
    {
        $this->wildcard->push(new Job('Test_Job'));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Wildcard queue can not be registered
     */
    public function testCannotBeRegistered()
    {
        $this->wildcard->register();
    }

    public function testNoQueuesPopsNull()
    {
        $this->assertNull($this->wildcard->pop());
    }
}
