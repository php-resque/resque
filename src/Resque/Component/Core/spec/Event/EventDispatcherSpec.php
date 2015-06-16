<?php

namespace spec\Resque\Component\Core\Event;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EventDispatcherSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Core\Event\EventDispatcher');
    }

    function it_is_an_event_dispatcher()
    {
        $this->shouldImplement('Resque\Component\Core\Event\EventDispatcherInterface');
    }

    function it_should_invoke_registered_callables_for_an_event(

    ) {

    }
}
