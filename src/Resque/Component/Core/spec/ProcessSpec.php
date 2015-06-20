<?php

namespace spec\Resque\Component\Core;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ProcessSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Core\Process');
    }

    function its_pid_is_mutable()
    {
        $this->setPid(4578)->shouldReturn($this);
        $this->getPid()->shouldReturn(4578);
    }

    function it_can_assume_current_pid()
    {
        $this->getPid()->shouldReturn(null);
        $this->setPidFromCurrentProcess()->shouldReturn($this);
        $this->getPid()->shouldReturn(getmypid());
    }
}
