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

    function it_is_trackable()
    {
        $this->shouldImplement('Resque\Component\Job\Model\TrackableJobInterface');
    }

    function it_provides_a_filter()
    {
        $this->shouldImplement('Resque\Component\Job\Model\FilterableJobInterface');
    }

    function it_id_is_mutable()
    {
        $this->setId('foo');
        $this->getId()->shouldReturn('foo');
    }

    function it_should_generate_id_if_id_is_null()
    {
        $this->setId(null);
        $id = $this->getId();
        $this->getId()->shouldReturn($id);
    }
}
