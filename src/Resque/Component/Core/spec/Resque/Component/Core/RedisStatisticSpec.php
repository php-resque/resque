<?php

namespace spec\Resque\Component\Core;

use PhpSpec\ObjectBehavior;
use Predis\ClientInterface;
use Prophecy\Argument;

class RedisStatisticSpec extends ObjectBehavior
{
    function let(
        ClientInterface $redis
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
