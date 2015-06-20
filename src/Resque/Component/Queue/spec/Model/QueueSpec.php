<?php

namespace spec\Resque\Component\Queue\Model;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\ResqueQueueEvents;
use Resque\Component\Queue\Storage\QueueStorageInterface;

class QueueSpec extends ObjectBehavior
{
    function let(QueueStorageInterface $storage, EventDispatcherInterface $eventDispatcher)
    {
        $this->beConstructedWith('spec', $storage, $eventDispatcher);
    }

    function its_initializable()
    {
        $this->shouldHaveType('Resque\Component\Queue\Model\Queue');
    }

    function its_a_queue()
    {
        $this->shouldImplement('Resque\Component\Queue\Model\QueueInterface');
    }

    function its_name_matches_constructor_param()
    {
        $this->getName()->shouldReturn('spec');
    }

    function its_name_is_mutable()
    {
        $this->setName('queue');
        $this->getName()->shouldReturn('queue');
    }

    function it_enquees_a_job(
        QueueStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        JobInterface $job
    ) {
        $eventDispatcher->dispatch(ResqueQueueEvents::JOB_PUSH, Argument::type('Resque\Component\Queue\Event\QueueJobEvent'))->shouldBeCalled();
        $storage->enqueue($this, $job)->shouldBeCalled()->willReturn(true);
        $eventDispatcher->dispatch(ResqueQueueEvents::JOB_PUSHED, Argument::type('Resque\Component\Queue\Event\QueueJobEvent'))->shouldBeCalled();
        $this->enqueue($job)->shouldReturn(true);
    }

    function it_does_not_emit_pushed_if_storage_fails(
        QueueStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        JobInterface $job
    ) {
        $eventDispatcher->dispatch(ResqueQueueEvents::JOB_PUSH, Argument::type('Resque\Component\Queue\Event\QueueJobEvent'))->shouldBeCalled();
        $storage->enqueue($this, $job)->shouldBeCalled()->willReturn(false);
        $eventDispatcher->dispatch(ResqueQueueEvents::JOB_PUSHED, Argument::type('Resque\Component\Queue\Event\QueueJobEvent'))->shouldNotBeCalled();
        $this->enqueue($job)->shouldReturn(false);
    }

    function it_dequeues_a_job(
        QueueStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        JobInterface $job
    ) {
        $storage->dequeue($this)->shouldBeCalled()->willReturn($job);

        $eventDispatcher->dispatch(ResqueQueueEvents::JOB_POPPED, Argument::type('Resque\Component\Queue\Event\QueueJobEvent'));
        $this->dequeue()->shouldReturn($job);
    }

    function it_returns_null_on_dequeue_when_no_jobs() {
        $this->dequeue()->shouldReturn(null);
    }

    function it_asks_storage_for_queue_length(
        QueueStorageInterface $storage
    ) {
        $storage->count($this)->shouldBeCalled()->willReturn(61);
        $this->count()->shouldReturn(61);
    }
}
