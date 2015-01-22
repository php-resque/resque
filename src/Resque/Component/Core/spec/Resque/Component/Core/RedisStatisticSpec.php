<?php

namespace spec\Resque\Component\Core;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Core\Redis\RedisClientInterface;

class RedisStatisticSpec extends ObjectBehavior
{
    function let(
        RedisClientInterface $redis
    ) {
        $this->beConstructedWith($redis);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Core\RedisStatistic');
    }

    function it_is_statistic()
    {
        $this->shouldImplement('Resque\Component\Statistic\StatisticInterface');
    }
}
