<?php

namespace Resque\Component\Queue\Tests\Queue;

use Resque\Redis\RedisQueue;
use Resque\Redis\RedisQueueRegistryAdapter;
use Resque\Redis\Test\ResqueTestCase;
use Resque\Component\Job\Model\Job;
use Resque\Component\Queue\WildcardQueue;

class WildcardQueueTest extends ResqueTestCase
{
    /**
     * @var WildcardQueue
     */
    protected $wildcard;

    /**
     * @var RedisQueueRegistryAdapter
     */
    protected $registry;

    public function setUp()
    {
        parent::setUp();

        $this->registry = new RedisQueueRegistryAdapter($this->redis);
        $this->wildcard = new WildcardQueue($this->registry);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Wildcard queue does not support pushing
     */
    public function testJobCannotBePushed()
    {
        $this->wildcard->push($this->getMock('Resque\Component\Job\Model\JobInterface'));
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

        $this->registry->register($queueBaz);
        $this->registry->register($queueFoo);

        $this->assertNotNull($this->wildcard->pop());
        $this->assertNotNull($this->wildcard->pop());
        $this->assertNull($this->wildcard->pop());
    }

    public function testPrefixOnlyPopsFromMatchingQueues()
    {
        $wildcard = new WildcardQueue($this->registry, 'foo');

        $this->assertSame('foo*', $wildcard->getName());

        $queueBaz = new RedisQueue($this->redis);
        $queueBaz->setName('baz');
        $queueBaz->push(new Job('Foo'));

        $queueFoo = new RedisQueue($this->redis);
        $queueFoo->setName('foo');
        $jobFoo = new Job('Foo');
        $queueFoo->push($jobFoo);

        $this->registry->register($queueBaz);
        $this->registry->register($queueFoo);

        $poppedJob = $wildcard->pop();
        $this->assertNotNull($poppedJob);
        $this->assertSame($jobFoo->getId(), $poppedJob->getId());
        $this->assertNull($wildcard->pop());
    }
}
