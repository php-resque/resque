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

    function it_saves_queues_to_redis(
        QueueInterface $queue,
        RedisClientInterface $redis
    ) {
        $this->save();
    }
}
