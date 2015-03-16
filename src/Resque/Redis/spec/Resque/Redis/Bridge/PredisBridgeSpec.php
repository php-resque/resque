<?php

namespace spec\Resque\Redis\Bridge;

use PhpSpec\ObjectBehavior;
use Predis\Client;
use Prophecy\Argument;

/**
 * Predis bridge
 *
 * Predis 1.x is a hard to spec against as it relies on magic functions, and provides
 * no interfaces or concrete implementation with it's supported methods defined.
 */
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
        $this->shouldImplement('Resque\Redis\RedisClientInterface');
    }
}
