<?php

namespace spec\Resque\Component\Job\Model;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class JobSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Job\Model\Job');
    }

    function it_is_a_job()
    {
        $this->shouldImplement('Resque\Component\Job\Model\JobInterface');
    }

    function it_is_a_trackable_job()
    {
        $this->shouldImplement('Resque\Component\Job\Model\TrackableJobInterface');
    }

    function it_provides_a_filter()
    {
        $this->shouldImplement('Resque\Component\Job\Model\FilterableJobInterface');
    }

    function it_always_has_an_id()
    {
        $this->getId()->shouldBeString();
    }

    function it_id_is_mutable()
    {
        $this->setId('foo');
        $this->getId()->shouldReturn('foo');
    }

    function it_job_class_should_be_mutable()
    {
        $this->getJobClass()->shouldReturn(null);
        $this->setJobClass('Acme\Job');
        $this->getJobClass()->shouldReturn('Acme\Job');

    }

    function it_can_encode_itself()
    {
        $this::encode($this)->shouldBeString();
    }

    function it_can_decode_a_job()
    {
        $this::decode('{"class":"Acme\\\\Job","args":[[]],"id":123}')->shouldReturnAnInstanceOf('Resque\Component\Job\Model\Job');
    }

    function it_should_not_allow_decode_to_return_job_on_invalid_json()
    {
        $this->shouldThrow(new \InvalidArgumentException('Invalid JSON'))->duringDecode('{asd$%^]');
    }
}
