<?php

namespace spec\Resque\Component\Job\Event;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\PerformantJobInterface;

class JobInstanceEventSpec extends ObjectBehavior
{
    function let(
        JobInterface $job,
        PerformantJobInterface $jobInstance
    ) {
        $this->beConstructedWith($job, $jobInstance);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Job\Event\JobInstanceEvent');
    }

    function it_should_have_the_job_it_was_constructed_with(
        JobInterface $job
    ) {
        $this->getJob()->shouldReturn($job);
    }

    function it_should_have_the_job_instance_it_was_constructed_with(
        PerformantJobInterface $jobInstance
    ) {
        $this->getJobInstance()->shouldReturn($jobInstance);
    }
}
