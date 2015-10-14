<?php

namespace spec\Resque\Component\Worker;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Job\Factory\JobInstanceFactoryInterface;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\PerformantJobInterface;

class WorkerSpec extends ObjectBehavior
{
    function let(
        JobInstanceFactoryInterface $jobInstanceFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->beConstructedWith($jobInstanceFactory, $eventDispatcher);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Worker\Worker');
    }

    function it_on_passes_job_arguments_to_target_class_correctly(
        JobInstanceFactoryInterface $jobInstanceFactory,
        JobInterface $job,
        PerformantJobInterface $targetClass
    ) {
        $args = array(
            1,
            array(
                'foo' => 'test',
            ),
            'key' => 'baz',
        );

        $job->getOriginQueue()->willReturn();
        $job->getId()->willReturn();

        $job->getArguments()->willReturn($args);
        $jobInstanceFactory->createPerformantJob($job)->shouldBeCalled()->willReturn($targetClass);
        $targetClass->perform($args)->shouldBeCalled(1);

        $this->perform($job);
    }
}
