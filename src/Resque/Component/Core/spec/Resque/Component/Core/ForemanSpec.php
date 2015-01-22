<?php

namespace spec\Resque\Component\Core;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
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
        $worker2->work()->shouldBeCalled();
        $worker3->work()->shouldBeCalled();

        $workers = array(
            $worker1,
            $worker2,
            $worker3
        );

        $this->work($workers, true);
    }

    function it_can_clean_up_dead_workers(
        $workerRegistry,
        WorkerInterface $worker,
        WorkerInterface $deadWorker1,
        WorkerInterface $deadWorker2
    ) {
        $workerRegistry->all()->shouldBeCalled()->willReturn(array($worker, $deadWorker1, $deadWorker2));

        $worker->getId()->shouldBeCalled()->willReturn('localhost:50:jobs');
        $deadWorker1->getId()->shouldBeCalled()->willReturn('localhost:1:jobs');
        $deadWorker1->getId()->shouldBeCalled()->willReturn('localhost:2:high,low');

        $workerRegistry->deregister($deadWorker1)->shouldBeCalled();
        $workerRegistry->deregister($deadWorker2)->shouldBeCalled();
        $workerRegistry->deregister($worker)->shouldNotBeCalled();

        $this->pruneDeadWorkers();
    }

    function it_does_not_clean_up_unknown_workers(
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
