<?php

namespace spec\Resque\Component\Queue\Event;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Model\QueueInterface;

class QueueJobEventSpec extends ObjectBehavior
{
    function let(QueueInterface $queue, JobInterface $job)
    {
        $this->beConstructedWith($queue, $job);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Queue\Event\QueueJobEvent');
    }

    function it_holds_queue(
        QueueInterface $queue
    ) {
        $this->getQueue()->shouldReturn($queue);
    }

    function it_holds_job(
        JobInterface $job
    ) {
        $this->getJob()->shouldReturn($job);
    }
}
