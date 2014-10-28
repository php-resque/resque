<?php

namespace Resque\Tests\Queue;

use Resque\Job;
use Resque\Queue;
use Resque\Queue\WildcardQueue;
use Resque\Tests\ResqueTestCase;

class WildcardQueueTest extends ResqueTestCase
{
    /**
     * @var \Resque\Queue\WildcardQueue
     */
    protected $wildcard;

    public function setUp()
    {
        parent::setUp();

        $this->wildcard = new WildcardQueue($this->redis);
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

    public function testPopsFromAllQueues()
    {
        $queueBaz = new Queue('baz', $this->redis);
        $queueBaz->push(new Job('Foo'));

        $queueFoo = new Queue('foo', $this->redis);
        $queueFoo->push(new Job('Foo'));

        $this->assertNotNull($this->wildcard->pop());
        $this->assertNotNull($this->wildcard->pop());
        $this->assertNull($this->wildcard->pop());
    }

    public function testPrefixOnlyPopsFromMatchingQueues()
    {
        $this->wildcard = new WildcardQueue($this->redis, 'foo');

        $this->assertSame('foo*', $this->wildcard->getName());

        $queueBaz = new Queue('baz', $this->redis);
        $jobBaz = new Job('Baz');
        $queueBaz->push($jobBaz);

        $queueFoo = new Queue('foo', $this->redis);
        $jobFoo = new Job('Foo');
        $queueFoo->push($jobFoo);

        $poppedJob = $this->wildcard->pop();
        $this->assertSame($jobFoo->getId(), $poppedJob->getId());
        $this->assertNull($this->wildcard->pop());
    }
}
