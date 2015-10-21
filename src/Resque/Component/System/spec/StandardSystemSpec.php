<?php

namespace spec\Resque\Component\System;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class StandardSystemSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\System\StandardSystem');
    }

    function it_is_a_system()
    {
        $this->shouldHaveType('Resque\Component\System\SystemInterface');
    }
}
