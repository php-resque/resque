<?php

namespace spec\Resque\Component\Worker\Factory;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Job\Factory\JobInstanceFactoryInterface;
use Resque\Component\Queue\Factory\QueueFactoryInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\System\SystemInterface;

class WorkerFactorySpec extends ObjectBehavior
{
    function let(
        QueueFactoryInterface $queueFactory,
        JobInstanceFactoryInterface $jobInstanceFactory,
        EventDispatcherInterface $eventDispatcher,
        SystemInterface $system
    ) {
        $this->beConstructedWith($queueFactory, $jobInstanceFactory, $eventDispatcher, $system);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Worker\Factory\WorkerFactory');
    }

    function it_implements_worker_factory_interface()
    {
        $this->shouldImplement('Resque\Component\Worker\Factory\WorkerFactoryInterface');
    }

    function it_constructs_workers()
    {
        $this->createWorker()->shouldReturnAnInstanceOf('Resque\Component\Worker\Worker');
    }

    function it_constructs_workers_from_worker_ids(
        QueueFactoryInterface $queueFactory,
        QueueInterface $queueLow,
        QueueInterface $queueHigh
    ) {
        $queueFactory->createQueue('high')->shouldBeCalled()->willReturn($queueHigh);
        $queueFactory->createQueue('low')->shouldBeCalled()->willReturn($queueLow);
        $this->createWorkerFromId('localhost:4753:high,low')->shouldReturnAnInstanceOf('Resque\Component\Worker\Worker');
    }

    function it_sets_the_workers_hostname_on_create(
        SystemInterface $system
    ) {
        $system->getHostname()->shouldBeCalled(1)->willReturn('wild.ones');
        $this->createWorker()->getHostname()->shouldEqual('wild.ones');
    }
}
