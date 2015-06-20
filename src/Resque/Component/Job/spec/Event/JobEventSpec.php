<?php

namespace spec\Resque\Component\Job\Event;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Job\Model\JobInterface;

class JobEventSpec extends ObjectBehavior
{
    function let(
        JobInterface $job
    ) {
        $this->beConstructedWith($job);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Job\Event\JobEvent');
    }

    function it_should_return_the_job_it_was_constructed_with(
        JobInterface $job
    ) {
        $this->getJob()->shouldReturn($job);
    }
}
