<?php

namespace spec\Resque\Component\Job\Event;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use \Exception;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Worker\Model\WorkerInterface;

class JobFailedEventSpec extends ObjectBehavior
{
    function let(
        JobInterface $job,
        Exception $exception,
        WorkerInterface $worker
    ) {
        $this->beConstructedWith($job, $exception, $worker);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Job\Event\JobFailedEvent');
    }

    function it_should_have_the_job_it_was_constructed_with(
        JobInterface $job
    ) {
        $this->getJob()->shouldReturn($job);
    }

    function it_should_have_the_exception_it_was_constructed_with(
        Exception $exception
    ) {
        $this->getException()->shouldReturn($exception);
    }

    function it_should_have_the_worker_it_was_constructed_with(
        WorkerInterface $worker
    ) {
        $this->getWorker()->shouldReturn($worker);
    }
}
