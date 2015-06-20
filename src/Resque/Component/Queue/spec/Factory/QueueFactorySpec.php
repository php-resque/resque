<?php

namespace spec\Resque\Component\Queue\Factory;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Queue\Storage\QueueStorageInterface;

class QueueFactorySpec extends ObjectBehavior
{
    function let(QueueStorageInterface $storage, EventDispatcherInterface $dispatcher)
    {
        $this->beConstructedWith($storage, $dispatcher);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Queue\Factory\QueueFactory');
    }

    function it_is_a_queue_factory()
    {
        $this->shouldImplement('Resque\Component\Queue\Factory\QueueFactoryInterface');
    }

    function it_constructs_queues()
    {
        $this->createQueue('low')->shouldReturnAnInstanceOf('Resque\Component\Queue\Model\QueueInterface');
    }
}
