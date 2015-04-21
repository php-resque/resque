<?php

namespace spec\Resque\Component\Queue\Model;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class QueueSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Queue\Model\Queue');
    }
}

//
//<?php
//
//namespace spec\Resque\Redis;
//
//use PhpSpec\ObjectBehavior;
//use Prophecy\Argument;
//use Resque\Component\Core\Event\EventDispatcherInterface;
//use Resque\Redis\RedisClientInterface;
//use Resque\Component\Job\Model\JobInterface;
//use Resque\Component\Queue\ResqueQueueEvents;
//
//class RedisQueueStorageSpec extends ObjectBehavior
//{
//    function let(
//        RedisClientInterface $redis,
//        EventDispatcherInterface $eventDispatcher
//    ) {
//        $this->beConstructedWith($redis, $eventDispatcher);
//    }
//
//    function it_is_initializable()
//    {
//        $this->shouldHaveType('Resque\Redis\RedisQueueStorage');
//    }
//
//    function it_has_no_name_by_default()
//    {
//        $this->getName()->shouldReturn(null);
//    }
//
//    function its_name_is_mutable()
//    {
//        $this->setName('foo')->shouldReturn($this);
//        $this->getName()->shouldReturn('foo');
//    }
//
//    function it_is_queue()
//    {
//        $this->shouldImplement('Resque\Component\Queue\Model\QueueInterface');
//    }
//
//    function it_allows_jobs_to_be_pushed_into_it(
//        RedisClientInterface $redis,
//        EventDispatcherInterface $eventDispatcher,
//        JobInterface $job
//    ) {
//        $this->setName('bar');
//        $eventDispatcher->dispatch(ResqueQueueEvents::JOB_PUSH, Argument::type('Resque\Component\Queue\Event\QueueJobEvent'))->shouldBeCalled();
//        $job->encode()->shouldBeCalled()->willReturn('{"encoded":"job"}');
//        $redis->rpush('queue:bar', '{"encoded":"job"}')->shouldBeCalled()->willReturn(1);
//        $eventDispatcher->dispatch(ResqueQueueEvents::JOB_PUSHED, Argument::type('Resque\Component\Queue\Event\QueueJobEvent'))->shouldBeCalled();
//        $this->push($job)->shouldReturn(true);
//    }
//
//    function it_pops_pushed_jobs(
//        RedisClientInterface $redis
//    ) {
//        $redis->
//        $this->pop()->shouldReturn();
//    }
//
//    function it_on_pop_with_no_jobs_returns_null(
//    ) {
//        $this->pop()->shouldReturn(null);
//    }
//
//    function it_can_count_pushed_jobs(
//        RedisClientInterface $redis
//    ) {
//        $this->setName('foo');
//        $redis->llen('queue:foo')->shouldBeCalled()->willReturn(2);
//        $this->count()->shouldReturn(2);
//    }
//
//    function it_can_remove_pushed_jobs_with_a_filter()
//    {
//        $this->remove([]);
//    }
//}
