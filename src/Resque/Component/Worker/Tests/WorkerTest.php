<?php

namespace Resque\Tests;

use PHPUnit_Framework_TestCase;
use Resque\Component\Core\Event\EventDispatcher;
use Resque\Redis\RedisQueue;
use Resque\Component\Job\Model\Job;
use Resque\Component\Job\ResqueJobEvents;
use Resque\Component\Job\Tests\Jobs\Simple;
use Resque\Component\Worker\ResqueWorkerEvents;
use Resque\Component\Worker\Worker;

class WorkerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Worker
     */
    protected $worker;

    public function setup()
    {
        parent::setup();

        $jobInstanceFactory = $this->getMock('Resque\Component\Job\Factory\JobInstanceFactoryInterface');
        $eventDispatcher = $this->getMock('Resque\Component\Core\Event\EventDispatcherInterface');
        $this->worker = new Worker($jobInstanceFactory, $eventDispatcher);
    }

    public function testSetId()
    {
        $this->worker->setId('fiddle-sticks');
        $this->assertSame('fiddle-sticks', (string)$this->worker);
    }

    public function testWorkerCanWorkOverMultipleQueues()
    {
        return $this->markTestIncomplete();

        $queueOne = new RedisQueue($this->redis);
        $queueOne->setName('queue1');
        $queueTwo = new RedisQueue($this->redis);
        $queueTwo->setName('queue2');

        $jobOne = new Job('Test_Job_1');
        $jobTwo = new Job('Test_Job_2');

        $queueOne->push($jobOne);
        $queueTwo->push($jobTwo);

        $this->worker->addQueue($queueOne);
        $this->worker->addQueue($queueTwo);

        $job = $this->worker->reserve();
        $this->assertEquals($queueOne, $job->getOriginQueue());

        $job = $this->worker->reserve();
        $this->assertEquals($queueTwo, $job->getOriginQueue());
    }

    public function testWorkerWorksQueuesInSpecifiedOrder()
    {
        return $this->markTestIncomplete();

        $queueHigh = new RedisQueue($this->redis);
        $queueHigh->setName('high');
        $queueMedium = new RedisQueue($this->redis);
        $queueMedium->setName('medium');
        $queueLow = new RedisQueue($this->redis);
        $queueLow->setName('low');

        $this->worker->addQueue($queueHigh);
        $this->worker->addQueue($queueMedium);
        $this->worker->addQueue($queueLow);

        // RedisQueue the jobs in a different order
        $queueLow->push(new Job('Test_Job_1'));
        $queueHigh->push(new Job('Test_Job_2'));
        $queueMedium->push(new Job('Test_Job_3'));

        // Now check we get the jobs back in the right queue order
        $job = $this->worker->reserve();
        $this->assertSame($queueHigh, $job->getOriginQueue());
        $job = $this->worker->reserve();
        $this->assertSame($queueMedium, $job->getOriginQueue());
        $job = $this->worker->reserve();
        $this->assertSame($queueLow, $job->getOriginQueue());
    }

    public function testWorkerDoesNotWorkOnUnknownQueues()
    {
        return $this->markTestIncomplete();

        $queueOne = new RedisQueue($this->redis);
        $queueOne->setName('queue1');
        $queueTwo = new RedisQueue($this->redis);
        $queueTwo->setName('queue2');

        $queueTwo->push(new Job('Test_Job'));

        $this->worker->addQueue($queueOne);

        $this->assertNull($this->worker->reserve());
    }

    /**
     * @dataProvider dataProviderWorkerPerformEvents
     *
     * @param int $expectedCount The number of times an event should have been triggered
     * @param string $eventName The event name in question
     * @param string $jobClass A job class to ask perform to do work on
     */
    public function testWorkerPerformEmitsCorrectEvents($expectedCount, $eventName, $jobClass)
    {
        return $this->markTestIncomplete();

        $eventDispatcher = new EventDispatcher();
        $this->worker = new Worker($this->getMock('Resque\Component\Job\Factory\JobInstanceFactoryInterface'), $eventDispatcher);

        $eventTriggered = 0;
        $eventDispatcher->addListener(
            $eventName,
            function () use (&$eventTriggered) {
                $eventTriggered++;
            }
        );

        $job = new Job($jobClass);
        $queue = new RedisQueue($this->redis);
        $queue->setName('foo');
        $job->setOriginQueue($queue);

        $this->worker->perform($job);

        $this->assertEquals(
            $expectedCount,
            $eventTriggered,
            sprintf(
                'Expected Worker->perform() to dispatch "%s" %d times, got %d for job class %s',
                $eventName,
                $expectedCount,
                $eventTriggered,
                $jobClass
            )
        );
    }

    public function dataProviderWorkerPerformEvents()
    {
        return array(
            // Job that doesn't implement "PerformantJobInterface".
            array(0, ResqueJobEvents::BEFORE_PERFORM, 'Resque\Component\Job\Tests\Jobs\NoPerformMethod'),
            array(0, ResqueJobEvents::PERFORMED, 'Resque\Component\Job\Tests\Jobs\NoPerformMethod'),
            array(1, ResqueJobEvents::FAILED, 'Resque\Component\Job\Tests\Jobs\NoPerformMethod'),
            // Normal, expected to work job.
            array(1, ResqueJobEvents::BEFORE_PERFORM, 'Resque\Component\Job\Tests\Jobs\Simple'),
            array(1, ResqueJobEvents::PERFORMED, 'Resque\Component\Job\Tests\Jobs\Simple'),
            array(0, ResqueJobEvents::FAILED, 'Resque\Component\Job\Tests\Jobs\Simple'),
            // Job that will fail.
            array(1, ResqueJobEvents::BEFORE_PERFORM, 'Resque\Component\Job\Tests\Jobs\Failure'),
            array(0, ResqueJobEvents::PERFORMED, 'Resque\Component\Job\Tests\Jobs\Failure'),
            array(1, ResqueJobEvents::FAILED, 'Resque\Component\Job\Tests\Jobs\Failure'),
        );
    }

    public function testBeforePerformEventCanStopWork()
    {
        $eventDispatcher = new EventDispatcher();
        $this->worker = new Worker($this->getMock('Resque\Component\Job\Factory\JobInstanceFactoryInterface'), $eventDispatcher);

        $eventDispatcher->addListener(
            'resque.job.before_perform',
            function () {
                throw new \Exception('Take a break');
            }
        );

        $eventTriggered = 0;
        $eventDispatcher->addListener(
            ResqueJobEvents::FAILED,
            function () use (&$eventTriggered) {
                $eventTriggered++;
            }
        );

        return $this->markTestIncomplete();

        $job = new Job('Resque\Component\Job\Tests\Jobs\Simple');
        $queue = new RedisQueue($this->redis);
        $queue->setName('baz');
        $job->setOriginQueue($queue);

        $this->assertFalse(
            $this->worker->perform($job),
            'Job was still performed even though "resque.job.before_perform" throw an exception'
        );
        $this->assertEquals(
            1,
            $eventTriggered,
            'Expected event "resque.job.failed" was triggered for thrown exception on "resque.job.before_perform"'
        );
    }

    public function testBeforeForkEvent()
    {
        return $this->markTestIncomplete();
        $eventDispatcher = new EventDispatcher();
        $this->worker = new Worker($this->getMock('Resque\Component\Job\Factory\JobInstanceFactoryInterface'), $eventDispatcher);

        $eventTriggered = 0;
        $eventDispatcher->addListener(
            ResqueWorkerEvents::BEFORE_FORK_TO_PERFORM,
            function () use (&$eventTriggered) {
                $eventTriggered++;
            }
        );

        $job = new Job('Resque\Component\Job\Tests\Jobs\Simple');

        $queue = new RedisQueue($this->redis);
        $queue->setName('jobs');
        $queue->push($job);

        $this->worker->work(0);

        $this->assertEquals(1, $eventTriggered);
    }

    public function testWorkerTracksCurrentJobCorrectly()
    {
        return $this->markTestIncomplete();
        $queue = new RedisQueue($this->redis);
        $queue->setName('jobs');

        $job = new Job('Resque\Component\Job\Tests\Jobs\Simple');
        $queue->push($job);

        $mockWorker = $this->getMock(
            'Resque\Component\Worker\Worker',
            array('workComplete'),
            array(array($queue))
        );
        $mockWorker
            ->expects($this->once())
            ->method('workComplete')
            ->will($this->returnValue(null));
        $mockWorker->work(0);

        $currentJob = $mockWorker->getCurrentJob();

        $this->assertNotNull($currentJob);
        $this->assertEquals($job->getId(), $currentJob->getId());
        $this->assertTrue($this->redis->exists('worker:' . $mockWorker));
        $redisCurrentJob = json_decode($this->redis->get('worker:' . $mockWorker), true);
        $payload = json_decode($redisCurrentJob['payload'], true);
        $this->assertEquals($job->getId(), $payload['id']);
    }

    public function testWorkerRecoversFromChildDirtyExit()
    {
        return $this->markTestIncomplete();
        $queue = new RedisQueue($this->redis);
        $queue->setName('jobs');

        $job = new Job('Resque\Component\Job\Tests\Jobs\DirtyExit');
        $queue->push($job);

        $test = $this;
        $eventDispatcher = new EventDispatcher();
        $callbackTriggered = false;
        $eventDispatcher->addListener(
            ResqueJobEvents::FAILED,
            function ($event) use (&$callbackTriggered, $test) {
                $callbackTriggered = true;
                $test->assertInstanceOf('Resque\Component\Job\Exception\DirtyExitException', $event->getException());
            }
        );

        $this->worker->work(0);

        $this->assertTrue($callbackTriggered);
    }

    public function testPausedWorkerDoesNotPickUpJobs()
    {
        return $this->markTestIncomplete();
        $queue = new RedisQueue($this->redis);
        $queue->setName('jobs');
        $queue->push(new Job('Resque\Component\Job\Tests\Jobs\Simple'));

        $this->worker->pause();

        $this->worker->work(0);

        $this->assertEquals(1, $queue->count());
    }

    public function testResumedWorkerPicksUpJobs()
    {
        return self::markTestSkipped();

        $this->worker->setLogger(new Resque_Log());
        $this->worker->pause();
        Resque::enqueue('jobs', 'Test_Job');
        $this->worker->work(0);
        $this->assertEquals(0, Resque_Stat::get('processed'));
        $this->worker->unPauseProcessing();
        $this->worker->work(0);
        $this->assertEquals(1, Resque_Stat::get('processed'));
    }

    public function testWorkerClearsItsStatusWhenNotWorking()
    {
        return self::markTestSkipped();

        Resque::enqueue('jobs', 'Test_Job');
        $this->worker->setLogger(new Resque_Log());
        $job = $this->worker->reserve();
        $this->worker->workingOn($job);
        $this->worker->doneWorking();
        $this->assertEquals(array(), $this->worker->job());
    }

    public function testWorkerRecordsWhatItIsWorkingOn()
    {
        return self::markTestSkipped();

        $this->worker->setLogger(new Resque_Log());
        $this->worker->registerWorker();

        $payload = array(
            'class' => 'Test_Job'
        );
        $job = new Resque_Job('jobs', $payload);
        $this->worker->workingOn($job);

        $job = $this->worker->job();
        $this->assertEquals('jobs', $job['queue']);
        if (!isset($job['run_at'])) {
            $this->fail('Job does not have run_at time');
        }
        $this->assertEquals($payload, $job['payload']);
    }

    public function testForkingCanBeDisabled()
    {
        $job = new Job('Resque\Component\Job\Tests\Jobs\Simple');

        $this->worker = $this->getMock(
            'Resque\Component\Worker\Worker',
            array('perform', 'reserve'),
            array(
                $this->getMock('Resque\Component\Job\Factory\JobInstanceFactoryInterface'),
                $this->getMock('Resque\Component\Core\Event\EventDispatcherInterface'),
            )
        );
        $this->worker->expects($this->at(0))->method('reserve')->will($this->returnValue($job));
        $this->worker->expects($this->at(1))->method('perform')->will($this->returnValue(null));
        $this->worker->expects($this->at(2))->method('reserve')->will($this->returnValue(null));

        $this->worker->setForkOnPerform(false);

        $this->worker->work(0); // This test fails if the worker forks, as perform is not marked as called in the parent
    }

    /**
     * @expectedException \Resque\Component\Core\Exception\ResqueRuntimeException
     */
    public function testCannotSetCurrentJobIfNotNull()
    {
        $this->worker->setCurrentJob(new Job());
        $this->worker->setCurrentJob(new Job());
    }
}
