<?php

namespace spec\Resque\Component\Job\Event;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Job\Model\JobInterface;

class JobEventSpec extends ObjectBehavior
{
    function let(
        JobInterface $job
    ) {
        $this->beConstructedWith($job);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Job\Event\JobEvent');
    }
}
