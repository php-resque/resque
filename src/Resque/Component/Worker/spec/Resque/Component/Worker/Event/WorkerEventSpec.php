<?php

namespace spec\Resque\Component\Worker\Event;

use PhpSpec\ObjectBehavior;
use Resque\Component\Worker\Model\WorkerInterface;

class WorkerEventSpec extends ObjectBehavior
{
    function let(
        WorkerInterface $worker
    ) {
        $this->beConstructedWith($worker);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Worker\Event\WorkerEvent');
    }

    function it_holds_worker(
        WorkerInterface $worker
    ) {
        $this->beConstructedWith($worker);
        $this->getWorker()->shouldReturn($worker);
    }
}
