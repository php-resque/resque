<?php

namespace spec\Resque\Component\Queue;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Queue\Model\QueueInterface;
use Resque\Component\Queue\Registry\QueueRegistryInterface;

class WildcardQueueSpec extends ObjectBehavior
{
    public function let(
        QueueRegistryInterface $registry
    ) {
        $this->beConstructedWith($registry);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Queue\WildcardQueue');
    }

    function it_can_not_enqueue_jobs(
        JobInterface $job
    ) {
        $this->shouldThrow('Exception')->during('enqueue', [$job]);
    }

    function it_can_not_count_jobs()
    {
        $this->shouldThrow('Exception')->during('count');
    }

    function its_prefix_is_null_by_default()
    {
        $this->getPrefix()->shouldReturn(null);
    }

    function it_has_no_jobs_if_there_are_no_queues(
        QueueRegistryInterface $registry
    ) {
        $registry->all()->willReturn([]);
        $this->dequeue()->shouldReturn(null);
    }

    function it_dequeues_jobs_from_all_queues(
        QueueRegistryInterface $registry,
        QueueInterface $queueBaz,
        QueueInterface $queueFoo,
        JobInterface $jobBaz,
        JobInterface $jobFoo
    ) {
        $registry->all()->willReturn([$queueBaz, $queueFoo]);

        $queueBaz->dequeue()->shouldBeCalled(1)->willReturn($jobBaz);
        $this->dequeue()->shouldReturn($jobBaz);

        $queueBaz->dequeue()->shouldBeCalled(1)->willReturn(null);
        $queueFoo->dequeue()->shouldBeCalled(1)->willReturn($jobFoo);
        $this->dequeue()->shouldReturn($jobFoo);

        $queueBaz->dequeue()->shouldBeCalled(1)->willReturn(null);
        $queueFoo->dequeue()->shouldBeCalled(1)->willReturn(null);
        $this->dequeue()->shouldReturn(null);
    }

    function it_dequeues_jobs_from_only_matching_queues(
        QueueRegistryInterface $registry,
        QueueInterface $queueBaz,
        QueueInterface $queueFoo,
        JobInterface $jobFoo
    ) {
        $this->beConstructedWith($registry, 'foo');

        $this->getPrefix()->shouldReturn('foo');

        $registry->all()->willReturn([$queueBaz, $queueFoo]);

        $queueBaz->getName()->willReturn('baz');
        $queueFoo->getName()->willReturn('foo');

        $queueBaz->dequeue()->shouldNotBeCalled();
        $queueFoo->dequeue()->shouldBeCalled(1)->willReturn($jobFoo);
        $this->dequeue()->shouldReturn($jobFoo);
    }
}
