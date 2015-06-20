<?php

namespace spec\Resque\Component\Core;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Queue\Factory\QueueFactoryInterface;
use Resque\Component\Queue\Model\QueueInterface;

class ResqueSpec extends ObjectBehavior
{
    function let(
        QueueFactoryInterface $queueFactory
    ) {
        $this->beConstructedWith($queueFactory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Core\Resque');
    }

    function it_asks_queue_registry_to_create_queue(
        QueueFactoryInterface $queueFactory,
        QueueInterface $queue
    ) {
        $queueFactory->createQueue('foo')->willReturn($queue);
        $this->getQueue('foo')->shouldReturn($queue);
    }

    function it_can_enqueue_a_job(
        QueueFactoryInterface $queueFactory,
        QueueInterface $queue
    ) {
        $queueFactory->createQueue('foo')->willReturn($queue);
        $this->enqueue('foo', 'test')->shouldReturnAnInstanceOf('Resque\Component\Job\Model\JobInterface');
    }
}
