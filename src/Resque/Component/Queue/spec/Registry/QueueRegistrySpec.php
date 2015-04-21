<?php

namespace spec\Resque\Component\Queue\Registry;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class QueueRegistrySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Queue\Registry\QueueRegistry');
    }
}
