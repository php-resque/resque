<?php

namespace spec\Resque\Component\Worker\Registry;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Worker\Registry\WorkerRegistryAdapterInterface;
use Resque\Redis\RedisClientInterface;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Worker\Factory\WorkerFactoryInterface;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\ResqueWorkerEvents;

class WorkerRegistrySpec extends ObjectBehavior
{
    function let(
        WorkerRegistryAdapterInterface $adapter,
        EventDispatcherInterface $eventDispatcher,
        WorkerFactoryInterface $workerFactory
    ) {
        $this->beConstructedWith($adapter, $eventDispatcher, $workerFactory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Worker\Registry\WorkerRegistry');
    }

    function it_is_a_worker_registry()
    {
        $this->shouldImplement('Resque\Component\Worker\Registry\WorkerRegistryInterface');
    }

    function it_registers_workers_via_its_adapter(
        WorkerRegistryAdapterInterface $adapter,
        EventDispatcherInterface $eventDispatcher,
        WorkerInterface $worker
    ) {
        $eventDispatcher->dispatch(
            ResqueWorkerEvents::REGISTERED,
            Argument::type('Resque\Component\Worker\Event\WorkerEvent')
        )->shouldBeCalled();

        $adapter->has($worker)->willReturn(false);
        $adapter->save($worker)->shouldBeCalled(1);

        $this->register($worker)->shouldReturn($this);
    }

    function it_wont_allow_registering_an_already_registered_worker(
        WorkerRegistryAdapterInterface $adapter,
        EventDispatcherInterface $eventDispatcher,
        WorkerInterface $worker
    ) {
        $eventDispatcher->dispatch(ResqueWorkerEvents::REGISTERED, Argument::any())->shouldNotBeCalled();

        $adapter->has($worker)->willReturn(true);

        $this->shouldThrow('Exception')->duringRegister($worker);
    }

    function it_does_not_have_workers_registered_by_default(
        WorkerRegistryAdapterInterface $adapter,
        WorkerInterface $worker
    ) {
        $adapter->has($worker)->shouldBeCalled()->willReturn(false);

        $this->isRegistered($worker)->shouldReturn(false);
    }

    function it_returns_adapter_result_for_is_registered(
        WorkerRegistryAdapterInterface $adapter,
        WorkerInterface $worker
    ) {
        $adapter->has($worker)->shouldBeCalled()->willReturn(true);

        $this->isRegistered($worker)->shouldReturn(true);
    }

    function it_removes_workers_from_redis_on_deregister(
        WorkerRegistryAdapterInterface $adapter,
        EventDispatcherInterface $eventDispatcher,
        WorkerInterface $worker
    ) {
        $eventDispatcher->dispatch(
            ResqueWorkerEvents::UNREGISTERED,
            Argument::type('Resque\Component\Worker\Event\WorkerEvent')
        )->shouldBeCalled();

        $adapter->delete($worker)->shouldBeCalled(1);

        $this->deregister($worker)->shouldReturn($this);
    }

    function it_gets_count_from_adapter(
        WorkerRegistryAdapterInterface $adapter
    ) {
        $adapter->count()->shouldBeCalled(1)->willReturn(456);

        $this->count()->shouldReturn(456);
    }

    function it_fetches_all_registered_workers(
        WorkerRegistryAdapterInterface $adapter,
        WorkerFactoryInterface $workerFactory,
        WorkerInterface $workerRemote,
        WorkerInterface $workerLocal
    ) {
        $adapter->all()->shouldBeCalled()->willReturn(array('remote:123', 'local:4556'));

        $workerFactory->createWorkerFromId('remote:123')->shouldBeCalled()->willReturn($workerRemote);
        $workerFactory->createWorkerFromId('local:4556')->shouldBeCalled()->willReturn($workerLocal);

        $this->all()->shouldReturn(array($workerRemote, $workerLocal));
    }

    function it_offloads_worker_persist_to_adapter(
        WorkerRegistryAdapterInterface $adapter,
        EventDispatcherInterface $eventDispatcher,
        WorkerInterface $worker
    ) {
        $eventDispatcher->dispatch(ResqueWorkerEvents::PERSISTED, Argument::any())->shouldBeCalled();

        $adapter->save($worker)->shouldBeCalled(1);

        $this->persist($worker)->shouldReturn($this);
    }

    function it_can_find_workers_by_id_if_registered(
        WorkerRegistryAdapterInterface $adapter,
        WorkerFactoryInterface $workerFactory,
        WorkerInterface $worker
    ) {
        $workerFactory->createWorkerFromId('compy:55:bar')->shouldBeCalled()->willReturn($worker);

        $adapter->has($worker)->shouldBeCalled()->willReturn(true);

        $this->findWorkerById('compy:55:bar')->shouldReturn($worker);
    }

    function it_returns_null_on_finding_unregistered_workers(
        WorkerRegistryAdapterInterface $adapter,
        WorkerFactoryInterface $workerFactory,
        WorkerInterface $worker
    ) {
        $workerFactory->createWorkerFromId('compy:55:foo')->shouldBeCalled()->willReturn($worker);

        $adapter->has($worker)->shouldBeCalled()->willReturn(false);

        $this->findWorkerById('compy:55:foo')->shouldReturn(null);
    }
}
