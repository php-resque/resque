<?php

namespace spec\Resque\Redis;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Redis\RedisClientInterface;

class RedisWorkerRegistryAdapterSpec extends ObjectBehavior
{
    function let(
        RedisClientInterface $redis
    ) {
        $this->beConstructedWith($redis);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Redis\RedisWorkerRegistryAdapter');
    }

    function it_is_a_worker_registry_adapter()
    {
        $this->shouldImplement('Resque\Component\Worker\Registry\WorkerRegistryAdapterInterface');
    }

    function it_is_redis_client_aware()
    {
        $this->shouldImplement('Resque\Redis\RedisClientAwareInterface');
    }

    function its_redis_client_is_mutable(
        RedisClientInterface $redis
    ) {
        $this->setRedisClient($redis)->shouldReturn($this);
    }

    function it_saves_workers_to_redis(
        RedisClientInterface $redis,
        WorkerInterface $worker
    ) {
        $worker->getId()->shouldBeCalled()->willReturn('host:123');
        $worker->getCurrentJob()->willReturn(null);

        $redis->sadd('workers', 'host:123')->shouldBeCalled();
        $redis->set('worker:host:123:started', Argument::any())->shouldBeCalled();
        $redis->del('worker:host:123')->shouldBeCalled();

        $this->save($worker);
    }

    /**
     * @todo need to sort out encoding. This only hard because of the date('c') calls with RedisWorkerRegistryAdapter.
     */
    function it_correctly_saves_a_workers_current_job_to_redis(
        RedisClientInterface $redis,
        WorkerInterface $worker,
        JobInterface $job
    ) {
        $worker->getId()->shouldBeCalled()->willReturn('foo:333');
        $worker->getCurrentJob()->shouldBeCalled()->willReturn($job);

        $job->encode()->shouldBeCalled()->willReturn('encoded-job');

        $redis->sadd('workers', 'foo:333')->shouldBeCalled();
        $redis->set('worker:foo:333:started', Argument::any())->shouldBeCalled();
        $redis->set('worker:foo:333', Argument::any())->shouldBeCalled();

        $this->save($worker);
    }

    function it_has_worker_when_it_is_in_worker_set(
        RedisClientInterface $redis,
        WorkerInterface $worker
    ) {
        $worker->getId()->shouldBeCalled()->willReturn('host:abc');
        $redis->sismember('workers', 'host:abc')->shouldBeCalled()->willReturn(1);
        $this->has($worker)->shouldReturn(true);
    }

    function it_does_not_have_worker_when_it_is_not_in_worker_set(
        WorkerInterface $worker
    ) {
        $this->has($worker)->shouldReturn(false);
    }

    function it_deletes_workers_from_redis(
        RedisClientInterface $redis,
        WorkerInterface $worker
    ) {
        $worker->getId()->shouldBeCalled()->willReturn('local:789');
        $redis->srem('workers', 'local:789')->shouldBeCalled()->willReturn(1);
        $redis->del('worker:local:789')->shouldBeCalled()->willReturn(1);
        $redis->del('worker:local:789:started')->shouldBeCalled()->willReturn(1);
        $this->delete($worker)->shouldReturn($this);
    }

    function it_correctly_counts_workers(
       RedisClientInterface $redis
    ) {
        $redis->scard('workers')->shouldBeCalled()->willReturn(0);
        $this->count()->shouldReturn(0);
        $redis->scard('workers')->shouldBeCalled()->willReturn(456);
        $this->count()->shouldReturn(456);
    }

    function it_returns_all_registered_workers(
        RedisClientInterface $redis
    ) {
        $redis->smembers('workers')->shouldBeCalled()->willReturn(array('remote:123', 'local:4556'));
        $this->all()->shouldReturn(array('remote:123', 'local:4556'));
    }
}
