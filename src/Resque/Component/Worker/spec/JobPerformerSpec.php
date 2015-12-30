<?php

namespace spec\Resque\Component\Worker;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Job\Factory\JobInstanceFactoryInterface;

class JobPerformerSpec extends ObjectBehavior
{
    function let(
        JobInstanceFactoryInterface $jobInstanceFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->beConstructedWith($jobInstanceFactory, $eventDispatcher);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Worker\JobPerformer');
    }

}
