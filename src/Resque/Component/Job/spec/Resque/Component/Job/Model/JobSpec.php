<?php

namespace spec\Resque\Component\Job\Model;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Queue\Model\QueueInterface;

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

    function it_is_a_filterable_job()
    {
        $this->shouldImplement('Resque\Component\Job\Model\FilterableJobInterface');
    }

    function it_is_origin_queue_aware()
    {
        $this->shouldHaveType('Resque\Component\Queue\Model\OriginQueueAwareInterface');
    }

    function it_always_has_an_id()
    {
        $this->getId()->shouldBeString();
    }

    function its_id_is_mutable()
    {
        $this->setId('foo');
        $this->getId()->shouldReturn('foo');
    }

    function its_job_class_is_mutable()
    {
        $this->getJobClass()->shouldReturn(null);
        $this->setJobClass('Acme\Job');
        $this->getJobClass()->shouldReturn('Acme\Job');
    }

    function its_arguments_are_mutable()
    {
        $this->setArguments(array('foo' => 'bar'));
        $this->getArguments()->shouldReturn(array('foo' => 'bar'));
    }

    function it_should_only_allow_an_array_as_arguments()
    {
        $this->shouldThrow(new \InvalidArgumentException('Supplied $args must be an array'))->duringSetArguments(new \stdClass);
    }

    function it_should_have_no_state_by_default()
    {
        $this->getState()->shouldReturn(null);
    }

    function its_state_is_mutable()
    {
        $this->setState('foo');
        $this->getState()->shouldReturn('foo');
    }

    function it_should_have_no_origin_queue_by_default()
    {
        $this->getOriginQueue()->shouldReturn(null);
    }

    function its_origin_queue_is_mutable(
        QueueInterface $queue
    ) {
        $this->setOriginQueue($queue)->shouldReturn($this);
        $this->getOriginQueue()->shouldReturn($queue);
    }

    function it_can_encode_itself()
    {
        $this->encode()->shouldBeString();
    }

    function it_can_decode_a_job()
    {
        $this->decode('{"class":"Acme\\\\Job","args":[[]],"id":123}')->shouldReturnAnInstanceOf('Resque\Component\Job\Model\Job');
    }

    function it_should_not_allow_decode_to_return_job_on_invalid_json()
    {
        $this->shouldThrow(new \InvalidArgumentException('Invalid JSON'))->duringDecode('{asd$%^]');
    }
}
