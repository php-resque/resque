<?php

namespace spec\Resque\Redis;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Redis\RedisClientInterface;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\Model\TrackableJobInterface;

class RedisJobTrackerSpec extends ObjectBehavior
{
    function let(RedisClientInterface $redis)
    {
        $this->beConstructedWith($redis);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Redis\RedisJobTracker');
    }

    function it_is_a_job_tracker()
    {
        $this->shouldImplement('Resque\Component\Job\Model\JobTrackerInterface');
    }

    function it_if_a_job_is_tracking_returns_true(
        RedisClientInterface $redis,
        JobInterface $job
    ) {
        $job->getId()->shouldBeCalled()->willReturn('hostname:pid:queues,asd');
        $redis->exists('job:hostname:pid:queues,asd:status')->shouldBeCalled()->willReturn(true);
        $this->isTracking($job)->shouldReturn(true);
    }

    function it_can_track_a_job_status(
        RedisClientInterface $redis,
        TrackableJobInterface $job
    ) {
        $job->getState()->shouldBeCalled()->willReturn('started');
        $job->getId()->shouldBeCalled()->willReturn('id');

        $redis->set('job:id:status', Argument::type('string'))->shouldBeCalled();
        $redis->expire(Argument::any(), Argument::any())->shouldNotBeCalled();

        $this->track($job)->shouldReturn(null);
    }

    function it_on_tracking_a_completion_status_sets_key_to_expire(
        RedisClientInterface $redis,
        TrackableJobInterface $job
    ) {
        $job->getState()->shouldBeCalled()->willReturn(JobInterface::STATE_COMPLETE);
        $job->getId()->shouldBeCalled()->willReturn('id');

        $redis->set('job:id:status', Argument::type('string'))->shouldBeCalled();
        $redis->expire('job:id:status', 86400)->shouldBeCalled();

        $this->track($job)->shouldReturn(null);
    }

    function it_can_get_the_stored_status_of_a_job(
        RedisClientInterface $redis,
        JobInterface $job
    ) {
        $job->getId()->shouldBeCalled()->willReturn('id');
        $redis->exists('job:id:status')->shouldBeCalled()->willReturn(true);
        $redis->get('job:id:status')->shouldBeCalled()->willReturn('{"status":"test"}');
        $this->get($job)->shouldReturn('test');
    }

    function it_returns_false_when_a_job_is_not_tracked_and_its_status_is_requested(
        RedisClientInterface $redis,
        JobInterface $job
    ) {
        $job->getId()->shouldBeCalled()->willReturn('id');
        $redis->exists('job:id:status')->shouldBeCalled()->willReturn(false);
        $this->get($job)->shouldReturn(false);
    }

    function it_stops_tracking_of_a_job(
        RedisClientInterface $redis,
        JobInterface $job
    ) {
        $job->getId()->willReturn('123');
        $redis->del('job:123:status')->shouldBeCalled();
        $this->stop($job);
    }

    function it_when_asked_if_completed_job_is_complete_returns_true(
        RedisClientInterface $redis,
        JobInterface $job
    ) {
        $job->getId()->willReturn('baz');
        $redis->exists('job:baz:status')->shouldBeCalled()->willReturn(true);
        $redis->get('job:baz:status')->shouldBeCalled()->willReturn('{"status": "complete"}');
        $this->isComplete($job)->shouldReturn(true);
    }

    function it_when_asked_if_job_is_complete_returns_false_when_it_is_not_complete(
        RedisClientInterface $redis,
        JobInterface $job
    ) {
        $job->getId()->willReturn('baz');
        $redis->exists('job:baz:status')->shouldBeCalled()->willReturn(true);
        $redis->get('job:baz:status')->shouldBeCalled()->willReturn('{"status": "processing"}');
        $this->isComplete($job)->shouldReturn(false);
    }
}
