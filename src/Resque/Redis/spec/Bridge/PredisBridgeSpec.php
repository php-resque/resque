<?php

namespace spec\Resque\Redis\Bridge;

use PhpSpec\ObjectBehavior;
use Predis\Client;
use Prophecy\Argument;

class PredisBridgeSpec extends ObjectBehavior
{
    function let(
        Client $predis
    ) {
        $this->beConstructedWith($predis);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Redis\Bridge\PredisBridge');
    }

    function it_is_a_redis_client()
    {
        $this->shouldHaveType('Resque\Redis\RedisClientInterface');
    }
}
