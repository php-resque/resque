<?php

namespace spec\Resque\Component\Core;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Core\Redis\RedisClientInterface;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Worker\Factory\WorkerFactoryInterface;
use Resque\Component\Worker\Model\WorkerInterface;

class RedisWorkerRegistrySpec extends ObjectBehavior
{
    function let(
        RedisClientInterface $redis,
        WorkerFactoryInterface $workerFactory
    ) {
        $this->beConstructedWith($redis, $workerFactory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Core\RedisWorkerRegistry');
    }

    function it_is_a_worker_registry()
    {
        $this->shouldImplement('Resque\Component\Worker\Registry\WorkerRegistryInterface');
    }

    function it_is_redis_client_aware()
    {
        $this->shouldImplement('Resque\Component\Core\Redis\RedisClientAwareInterface');
    }

    function its_redis_client_is_mutable(
        RedisClientInterface $redis
    ) {
        $this->setRedisClient($redis)->shouldReturn($this);
    }

    function it_registers_workers_to_redis(
        RedisClientInterface $redis,
        WorkerInterface $worker
    ) {
        $worker->getId()->shouldBeCalled()->willReturn('host:123');
        $redis->sismember('workers', 'host:123')->shouldBeCalled()->willReturn(false);
        $redis->sadd('workers', 'host:123')->shouldBeCalled();
        $redis->set('worker:host:123:started', Argument::any())->shouldBeCalled();
        $this->register($worker)->shouldReturn($this);
    }

    function it_wont_allow_registering_an_already_registered_worker(
        RedisClientInterface $redis,
        WorkerInterface $worker
    ) {
        $worker->getId()->shouldBeCalled()->willReturn('host:123');
        $redis->sismember('workers', 'host:123')->shouldBeCalled()->willReturn(true);
        $this->shouldThrow('Exception')->duringRegister($worker);
    }

    function it_does_not_have_workers_registered_by_default(
        WorkerInterface $worker
    ) {
        $this->isRegistered($worker)->shouldReturn(false);
    }

    function it_returns_true_when_a_worker_is_registered(
        RedisClientInterface $redis,
        WorkerInterface $worker
    ) {
        $worker->getId()->shouldBeCalled()->willReturn('host:abc');
        $redis->sismember('workers', 'host:abc')->shouldBeCalled()->willReturn(1);
        $this->isRegistered($worker)->shouldReturn(true);
    }

    function it_shutdowns_and_removes_workers_from_redis_on_deregister(
        RedisClientInterface $redis,
        WorkerInterface $worker
    ) {
        $worker->getId()->shouldBeCalled()->willReturn('local:789');
        $worker->halt()->shouldBeCalled();
        $redis->srem('workers', 'local:789')->shouldBeCalled()->willReturn(1);
        $redis->del('worker:local:789')->shouldBeCalled()->willReturn(1);
        $redis->del('worker:local:789:started')->shouldBeCalled()->willReturn(1);
        $this->deregister($worker)->shouldReturn($this);
    }

    function it_correctly_counts_registered_workers(
       RedisClientInterface $redis
    ) {
        $redis->scard('workers')->shouldBeCalled()->willReturn(0);
        $this->count()->shouldReturn(0);
        $redis->scard('workers')->shouldBeCalled()->willReturn(456);
        $this->count()->shouldReturn(456);
    }

    function it_fetches_all_registered_workers(
        RedisClientInterface $redis,
        WorkerFactoryInterface $workerFactory,
        WorkerInterface $workerRemote,
        WorkerInterface $workerLocal
    ) {
        $redis->smembers('workers')->shouldBeCalled()->willReturn(array('remote:123', 'local:4556'));
        $workerFactory->createWorkerFromId('remote:123')->shouldBeCalled()->willReturn($workerRemote);
        $workerFactory->createWorkerFromId('local:4556')->shouldBeCalled()->willReturn($workerLocal);
        $this->all()->shouldReturn(array($workerRemote, $workerLocal));
    }

    function it_on_worker_persist_with_current_job_saves_worker_state_to_redis(
        RedisClientInterface $redis,
        WorkerInterface $worker,
        JobInterface $job
    ) {
        $worker->getCurrentJob()->shouldBeCalled()->willReturn($job);
        $worker->getId()->shouldBeCalled()->willReturn('foo:333');
        $job->encode()->shouldBeCalled()->willReturn('encoded-job');
        $redis->set('worker:foo:333', Argument::any())->shouldBeCalled();
        $this->persist($worker)->shouldReturn($this);
    }

    function it_on_worker_persist_with_no_current_job_removes_the_worker_entry_from_redis(
        RedisClientInterface $redis,
        WorkerInterface $worker
    ) {
        $worker->getCurrentJob()->shouldBeCalled()->willReturn(null);
        $worker->getId()->shouldBeCalled()->willReturn('foo:666');
        $redis->del('worker:foo:666')->shouldBeCalled();
        $this->persist($worker)->shouldReturn($this);
    }

    function it_can_find_workers_by_id_if_registered(
        RedisClientInterface $redis,
        WorkerFactoryInterface $workerFactory,
        WorkerInterface $worker
    ) {
        $workerFactory->createWorkerFromId('compy:55:bar')->shouldBeCalled()->willReturn($worker);
        $worker->getId()->shouldBeCalled()->willReturn('compy:55:bar');
        $redis->sismember('workers', 'compy:55:bar')->shouldBeCalled()->willReturn(true);
        $this->findWorkerById('compy:55:bar')->shouldReturn($worker);
    }

    function it_returns_null_on_finding_unregistered_workers(
        RedisClientInterface $redis,
        WorkerFactoryInterface $workerFactory,
        WorkerInterface $worker
    ) {
        $workerFactory->createWorkerFromId('compy:55:foo')->shouldBeCalled()->willReturn($worker);
        $worker->getId()->shouldBeCalled()->willReturn('compy:55:foo');
        $redis->sismember('workers', 'compy:55:foo')->shouldBeCalled()->willReturn(false);
        $this->findWorkerById('compy:55:foo')->shouldReturn(null);
    }
}
