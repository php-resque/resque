<?php

namespace spec\Resque\Redis;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RedisQueueRegistryAdapterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Redis\RedisQueueRegistryAdapter');
    }
}
