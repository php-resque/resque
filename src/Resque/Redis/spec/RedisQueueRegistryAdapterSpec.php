<?php

namespace spec\Resque\Redis;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Redis\RedisClientInterface;

class RedisQueueRegistryAdapterSpec extends ObjectBehavior
{
    function let(RedisClientInterface $redis)
    {
        $this->beConstructedWith($redis);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Redis\RedisQueueRegistryAdapter');
    }

    function it_is_a_queue_registry_adapter()
    {
        $this->shouldImplement('Resque\Component\Queue\Registry\QueueRegistryAdapterInterface');
    }

    function it_can_tell_if_a_queue_is_already_registered(
        QueueInterface $queue,
        RedisClientInterface $redis
    ) {
        $queue->getName()->shouldBeCalled()->willReturn('foo');
        $this->has($queue)->shouldReturn(false);
        $redis->exists('queue:foo')->willReturn(1);
        $this->has($queue)->shouldReturn(true);
        $redis->exists('queue:foo')->willReturn(0);
        $this->has($queue)->shouldReturn(false);
    }

    function it_saves_queues_to_redis(
        QueueInterface $queue,
        RedisClientInterface $redis
    ) {
        $queue->getName()->shouldBeCalled()->willReturn('high');
        $redis->sadd('queues', 'high')->shouldBeCalled();
        $this->save($queue);
    }

    function it_deletes_queue_from_redis(
        QueueInterface $queue,
        RedisClientInterface $redis
    ) {
        $queue->getName()->shouldBeCalled()->willReturn('high');
        $redis->multi()->shouldBeCalled();
        $redis->llen('queue:high')->shouldBeCalled();
        $redis->del('queue:high')->shouldBeCalled();
        $redis->srem('queues', 'high')->shouldBeCalled();
        $redis->exec()->shouldBeCalled()->willReturn(array(5));
        $this->delete($queue)->shouldReturn(5);
    }

    function it_loads_all_queue_names_from_redis(
        RedisClientInterface $redis
    ) {
        $redis->smembers('queues')->shouldBeCalled()->willReturn(array('high', 'low', 'medium'));
        $this->all()->shouldReturn(array('high', 'low', 'medium'));
    }
}
