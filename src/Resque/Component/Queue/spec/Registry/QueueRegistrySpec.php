<?php

namespace spec\Resque\Component\Queue\Registry;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Queue\Factory\QueueFactoryInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Queue\Registry\QueueRegistryAdapterInterface;

class QueueRegistrySpec extends ObjectBehavior
{
    public function let(
        EventDispatcherInterface $eventDispatcher,
        QueueRegistryAdapterInterface $adapter,
        QueueFactoryInterface $factory
    ) {
       $this->beConstructedWith($eventDispatcher, $adapter, $factory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Queue\Registry\QueueRegistry');
    }

    function it_constructs_queue_names_in_to_queue_objects(
        QueueRegistryAdapterInterface $adapter,
        QueueFactoryInterface $factory,
        QueueInterface $queue
    ) {
        $adapter->all()->willReturn(array('important-things'));
        $factory->createQueue('important-things')->willReturn($queue);

        $this->all()->shouldReturn(array('important-things' => $queue));
    }
}
