<?php

namespace spec\Resque\Component\Core;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Core\Process;
use Resque\Component\core\ResqueEvents;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\Registry\WorkerRegistryInterface;

class ForemanSpec extends ObjectBehavior
{
    function let(
        WorkerRegistryInterface $workerRegistry,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->beConstructedWith($workerRegistry, $eventDispatcher);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Core\Foreman');
    }

    // $this->work is tested with phpunit, due to process forking. I can't work out how to do it sanely in phpspec.

    function it_cleans_up_dead_workers(
        WorkerRegistryInterface $workerRegistry,
        WorkerInterface $worker,
        WorkerInterface $deadWorker1,
        WorkerInterface $deadWorker2,
        Process $workerProcess,
        Process $deadWorker1Process,
        Process $deadWorker2Process
    ) {
        $workerRegistry->all()->shouldBeCalled()->willReturn(array($worker, $deadWorker1, $deadWorker2));

        $worker->getId()->shouldBeCalled()->willReturn('localhost:50:jobs');
        $worker->getProcess()->shouldBeCalled()->willReturn($workerProcess);
        $workerProcess->getPid()->shouldBeCalled()->willReturn('50');
        $deadWorker1->getId()->shouldBeCalled()->willReturn('localhost:1:jobs');
        $deadWorker1->getProcess()->shouldBeCalled()->willReturn($deadWorker1Process);
        $deadWorker1Process->getPid()->shouldBeCalled()->willReturn('1');
        $deadWorker2->getId()->shouldBeCalled()->willReturn('localhost:2:high,low');
        $deadWorker2->getProcess()->shouldBeCalled()->willReturn($deadWorker2Process);
        $deadWorker2Process->getPid()->shouldBeCalled()->willReturn('2');

        $workerRegistry->deregister($deadWorker1)->shouldBeCalled();
        $workerRegistry->deregister($deadWorker2)->shouldBeCalled();
        $workerRegistry->deregister($worker)->shouldNotBeCalled();

        $this->pruneDeadWorkers();
    }

    function it_does_not_clean_up_workers_on_another_host(
        $workerRegistry,
        WorkerInterface $localWorker,
        WorkerInterface $remoteWorker
    ) {
        $workerRegistry->all()->shouldBeCalled()->willReturn([$localWorker, $remoteWorker]);

        $localWorker->getId()->shouldBeCalled()->willReturn('localhost:1:jobs'); // @todo deal with hostname
        $remoteWorker->getId()->shouldBeCalled()->willReturn('my.other.host:1:jobs');

        $workerRegistry->deregister($localWorker)->shouldBeCalled();
        $workerRegistry->deregister($remoteWorker)->shouldNotBeCalled();

        $this->pruneDeadWorkers();
    }
}
