<?php

namespace spec\Resque\Component\Job\Event;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class JobEventSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Job\Event\JobEvent');
    }
}
