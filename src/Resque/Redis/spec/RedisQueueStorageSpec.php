<?php

namespace spec\Resque\Redis;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Redis\RedisClientInterface;
use Resque\Component\Job\Model\JobInterface;

class RedisQueueStorageSpec extends ObjectBehavior
{
    function let(RedisClientInterface $redis)
    {
        $this->beConstructedWith($redis);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Redis\RedisQueueStorage');
    }

    function it_is_queue_storage()
    {
        $this->shouldImplement('Resque\Component\Queue\Storage\QueueStorageInterface');
    }

    function it_pushes_enqueued_jobs_into_redis(
        RedisClientInterface $redis,
        QueueInterface $queue,
        JobInterface $job
    ) {
        $queue->getName()->shouldBeCalled()->willReturn('bar');
        $job->encode()->shouldBeCalled()->willReturn('{"encoded":"job"}');
        $redis->rpush('queue:bar', '{"encoded":"job"}')->shouldBeCalled()->willReturn(1);
        $this->enqueue($queue, $job)->shouldReturn(true);
    }

    function it_pops_jobs_from_redis_list(
        RedisClientInterface $redis,
        QueueInterface $queue
    ) {
        $queue->getName()->shouldBeCalled()->willReturn('high');
        $redis->lpop('queue:high')->shouldBeCalled()->willReturn('{"class":"stdClass","args":[[]],"id":"123"}');
        $this->dequeue($queue)->shouldReturnAnInstanceOf('Resque\Component\Job\Model\JobInterface');
    }

    function it_on_pop_with_no_jobs_returns_null(
        QueueInterface $queue
    ) {
        $this->dequeue($queue)->shouldReturn(null);
    }

    function it_can_count_pushed_jobs(
        RedisClientInterface $redis,
        QueueInterface $queue
    ) {
        $queue->getName()->shouldBeCalled()->willReturn('foo');
        $redis->llen('queue:foo')->shouldBeCalled()->willReturn(2);
        $this->count($queue)->shouldReturn(2);
    }

    function it_can_remove_pushed_jobs_with_a_filter(
        QueueInterface $queue
    ) {
        $this->remove($queue, array());
    }
}
