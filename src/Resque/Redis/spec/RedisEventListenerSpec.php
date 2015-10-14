<?php

namespace spec\Resque\Redis;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Redis\RedisClientInterface;

class RedisEventListenerSpec extends ObjectBehavior
{
    function let(RedisClientInterface $redis)
    {
        $this->beConstructedWith($redis);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Redis\RedisEventListener');
    }

    function it_is_redis_client_aware()
    {
        $this->shouldHaveType('Resque\Redis\RedisClientAwareInterface');
    }

    function it_disconnects_redis_client(RedisClientInterface $redis)
    {
        $redis->disconnect()->shouldBecalled();
        $this->disconnectFromRedis();
    }
}
