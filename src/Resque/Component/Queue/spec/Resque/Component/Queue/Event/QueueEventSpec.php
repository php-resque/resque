<?php

namespace spec\Resque\Component\Queue\Event;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Queue\Model\QueueInterface;

class QueueEventSpec extends ObjectBehavior
{
    function let(QueueInterface $queue)
    {
        $this->beConstructedWith($queue);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Queue\Event\QueueEvent');
    }
}
