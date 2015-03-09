<?php

namespace spec\Resque\Component\Core;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Queue\Registry\QueueRegistryInterface;

class ResqueSpec extends ObjectBehavior
{
    function let(
        QueueRegistryInterface $registry
    ) {
        $this->beConstructedWith($registry);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Core\Resque');
    }

    function it_asks_queue_registry_to_create_queue(
        QueueRegistryInterface $registry,
        QueueInterface $queue
    ) {
        $registry->createQueue('foo')->willReturn($queue);
        $this->getQueue('foo')->shouldReturn($queue);
    }

    function it_can_enqueue_a_job(
        QueueRegistryInterface $registry,
        QueueInterface $queue
    ) {
        $registry->createQueue('foo')->willReturn($queue);
        $this->enqueue('foo', 'test')->shouldReturnAnInstanceOf('Resque\Component\Job\Model\JobInterface');
    }
}
