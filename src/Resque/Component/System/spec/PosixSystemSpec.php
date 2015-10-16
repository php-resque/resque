<?php

namespace spec\Resque\Component\System;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PosixSystemSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\System\PosixSystem');
    }

    function it_is_a_system()
    {
        $this->shouldHaveType('Resque\Component\System\SystemInterface');
    }
}
