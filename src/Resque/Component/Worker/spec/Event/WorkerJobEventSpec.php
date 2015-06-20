<?php

namespace spec\Resque\Component\Worker\Event;

use PhpSpec\ObjectBehavior;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Worker\Model\WorkerInterface;

class WorkerJobEventSpec extends ObjectBehavior
{
    function let(
        WorkerInterface $worker,
        JobInterface $job
    ) {
        $this->beConstructedWith($worker, $job);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Worker\Event\WorkerJobEvent');
    }

    function it_holds_worker(
        WorkerInterface $worker,
        JobInterface $job
    ) {
        $this->beConstructedWith($worker, $job);
        $this->getWorker()->shouldReturn($worker);
    }

    function it_holds_job(
        WorkerInterface $worker,
        JobInterface $job
    ) {
        $this->beConstructedWith($worker, $job);
        $this->getJob()->shouldReturn($job);
    }
}
