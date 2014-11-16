<?php

namespace Resque\Tests\Queue;

use Resque\Component\Core\RedisQueue;
use Resque\Component\Core\RedisQueueRegistry;
use Resque\Component\Core\Test\ResqueTestCase;
use Resque\Component\Job\Model\Job;
use Resque\Component\Queue\WildcardQueue;

class WildcardQueueTest extends ResqueTestCase
{
    /**
     * @var WildcardQueue
     */
    protected $wildcard;

    /**
     * @var RedisQueueRegistry
     */
    protected $registry;

    public function setUp()
    {
        parent::setUp();

        $this->registry = new RedisQueueRegistry($this->redis);
        $this->wildcard = new WildcardQueue($this->registry);
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
        $queueBaz = new RedisQueue($this->redis);
        $queueBaz->setName('baz');
        $queueBaz->push(new Job('Foo'));

        $queueFoo = new RedisQueue($this->redis);
        $queueFoo->setName('foo');
        $queueFoo->push(new Job('Foo'));

        $this->assertNotNull($this->wildcard->pop());
        $this->assertNotNull($this->wildcard->pop());
        $this->assertNull($this->wildcard->pop());
    }

    public function testPrefixOnlyPopsFromMatchingQueues()
    {
        $this->wildcard = new WildcardQueue($this->registry, 'foo');

        $this->assertSame('foo*', $this->wildcard->getName());

        $queueBaz = new RedisQueue($this->redis);
        $queueBaz->setName('baz');
        $queueBaz->push(new Job('Foo'));

        $queueFoo = new RedisQueue($this->redis);
        $queueFoo->setName('foo');
        $jobFoo = new Job('Foo');
        $queueFoo->push($jobFoo);

        $poppedJob = $this->wildcard->pop();
        $this->assertNotNull($poppedJob);
        $this->assertSame($jobFoo->getId(), $poppedJob->getId());
        $this->assertNull($this->wildcard->pop());
    }
}
