<?php

namespace spec\Resque\Component\Core;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Core\Process;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\Registry\WorkerRegistryInterface;

class ForemanSpec extends ObjectBehavior
{
    function let(
        WorkerRegistryInterface $workerRegistry
    ) {
        $this->beConstructedWith($workerRegistry);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Core\Foreman');
    }

    function it_starts_workers(
        WorkerInterface $worker1,
        WorkerInterface $worker2,
        WorkerInterface $worker3
    ) {
        $me = getmypid();

        $worker1->work()->shouldBeCalled();
        $worker1->setProcess(Argument::type('Resque\Component\Core\Process'))->shouldBeCalled();
        $worker1->getId()->shouldBeCalled()->willReturn('earth:1:lunch');
        $worker2->work()->shouldBeCalled();
        $worker2->setProcess(Argument::type('Resque\Component\Core\Process'))->shouldBeCalled();
        $worker2->getId()->shouldBeCalled()->willReturn('earth:2:high');
        $worker3->work()->shouldBeCalled();
        $worker3->setProcess(Argument::type('Resque\Component\Core\Process'))->shouldBeCalled();
        $worker3->getId()->shouldBeCalled()->willReturn('earth:3:test');

        $workers = array(
            $worker1,
            $worker2,
            $worker3
        );

        $this->work($workers);
    }

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
