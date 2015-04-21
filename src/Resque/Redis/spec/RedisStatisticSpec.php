<?php

namespace spec\Resque\Redis;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Redis\RedisClientInterface;

class RedisStatisticSpec extends ObjectBehavior
{
    function let(
        RedisClientInterface $redis
    ) {
        $this->beConstructedWith($redis);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Redis\RedisStatistic');
    }

    function it_is_statistic()
    {
        $this->shouldImplement('Resque\Component\Statistic\StatisticInterface');
    }

    function it_increments_stat_by_one(
        RedisClientInterface $redis
    ) {
        $redis->incrby('stat:incr', 1)->shouldBeCalled()->willReturn(true);
        $this->increment('incr')->shouldReturn(true);
    }

    function it_increments_stat_by_N(
        RedisClientInterface $redis
    ) {
        $redis->incrby('stat:incr', 12)->shouldBeCalled()->willReturn(true);
        $this->increment('incr', 12)->shouldReturn(true);
    }

    function it_decrements_stat_by_defaut(
        RedisClientInterface $redis
    ) {
        $redis->decrby('stat:decr', 1)->shouldBeCalled()->willReturn(true);
        $this->decrement('decr')->shouldReturn(true);
    }

    function it_decrements_stat_by_N(
        RedisClientInterface $redis
    ) {
        $redis->decrby('stat:decrx', 11)->shouldBeCalled()->willReturn(true);
        $this->decrement('decrx', 11)->shouldReturn(true);
    }

    function it_gets_stat_by_name(
        RedisClientInterface $redis
    ) {
        $redis->get('stat:decrx')->shouldBeCalled()->willReturn(123);
        $this->get('decrx')->shouldReturn(123);
    }

    function it_returns_0_when_name_is_unknown(
        RedisClientInterface $redis
    ) {
        $redis->get('stat:unknown')->shouldBeCalled()->willReturn(false);
        $this->get('unknown')->shouldReturn(0);
    }

    function it_can_clear_stat(
        RedisClientInterface $redis
    ) {
        $redis->del('stat:test')->shouldBeCalled()->willReturn(true);
        $this->clear('test', 11)->shouldReturn(true);
    }
}
