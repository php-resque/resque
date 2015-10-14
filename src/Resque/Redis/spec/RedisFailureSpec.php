<?php

namespace spec\Resque\Redis;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Redis\RedisClientInterface;

class RedisFailureSpec extends ObjectBehavior
{
    function let(RedisClientInterface $redis)
    {
        $this->beConstructedWith($redis);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Redis\RedisFailure');
    }

    function it_is_failure_handler()
    {
        $this->shouldHaveType('Resque\Component\Job\Failure\FailureInterface');
    }

    function it_fetches_number_of_failed_jobs_with_llen(RedisClientInterface $redis)
    {
        $redis->llen('failed')->shouldBeCalled()->willReturn(2);
        $this->count()->shouldReturn(2);
    }

    function it_clears_list_failed_jobs(RedisClientInterface $redis)
    {
        $redis->del('failed')->shouldBeCalled();
        $this->clear();
    }

    function it_saves_failed_jobs_to_redis(
        RedisClientInterface $redis,
        JobInterface $job,
        \Exception $exception,
        WorkerInterface $worker
    ) {
        $redis->rpush('failed', Argument::containingString('"payload"'))->shouldBeCalled();

        $this->save(
            $job,
            $exception,
            $worker
        );
    }
}
