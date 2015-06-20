<?php

namespace spec\Resque\Component\Job\Factory;

use PhpSpec\ObjectBehavior;
use Resque\Component\Job\Model\JobInterface;

class JobInstanceFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Job\Factory\JobInstanceFactory');
    }

    function it_is_job_instance_factory()
    {
        $this->shouldHaveType('Resque\Component\Job\Factory\JobInstanceFactoryInterface');
    }

    function it_should_construct_performant_job(
        JobInterface $job
    ) {
        $job->getJobClass()->shouldBeCalled()->willReturn('Resque\Component\Job\Tests\Jobs\Simple');
        $this->createPerformantJob($job)->shouldReturnAnInstanceOf('Resque\Component\Job\PerformantJobInterface');
    }

    function it_should_throw_on_job_not_found(
        JobInterface $job
    ) {
        $job->getJobClass()->shouldBeCalled()->willReturn('Resque\Tests\Jobs\NonExistent');
        $this->shouldThrow('Resque\Component\Job\Exception\JobNotFoundException')->duringCreatePerformantJob($job);
    }
}
