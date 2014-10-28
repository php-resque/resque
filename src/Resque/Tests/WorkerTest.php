<?php

namespace Resque\Tests;

use Resque\Event\EventDispatcher;
use Resque\Job;
use Resque\Queue;
use Resque\Queue\WildcardQueue;
use Resque\Tests\Jobs\Simple;
use Resque\Worker;

class WorkerTest extends ResqueTestCase
{
    public function testSetId()
    {
        $worker = new Worker();
        $worker->setId('fiddle-sticks');
        $this->assertSame('fiddle-sticks', (string)$worker);
    }

    public function testWorkerCanWorkOverMultipleQueues()
    {
        $queueOne = new Queue('queue1');
        $queueOne->setRedisClient($this->redis);
        $queueTwo = new Queue('queue2');
        $queueTwo->setRedisClient($this->redis);

        $worker = new Worker(
            array(
                $queueOne,
                $queueTwo,
            )
        );

        $jobOne = new Job('Test_Job_1');
        $jobTwo = new Job('Test_Job_2');

        $queueOne->push($jobOne);
        $queueTwo->push($jobTwo);

        $job = $worker->reserve();
        $this->assertEquals($queueOne, $job->getOriginQueue());

        $job = $worker->reserve();
        $this->assertEquals($queueTwo, $job->getOriginQueue());
    }

    public function testWorkerWorksQueuesInSpecifiedOrder()
    {
        $queueHigh = new Queue('high');
        $queueHigh->setRedisClient($this->redis);
        $queueMedium = new Queue('medium');
        $queueMedium->setRedisClient($this->redis);
        $queueLow = new Queue('low');
        $queueLow->setRedisClient($this->redis);

        $worker = new Worker(
            array(
                $queueHigh,
                $queueMedium,
                $queueLow,
            )
        );

        // Queue the jobs in a different order
        $queueLow->push(new Job('Test_Job_1'));
        $queueHigh->push(new Job('Test_Job_2'));
        $queueMedium->push(new Job('Test_Job_3'));

        // Now check we get the jobs back in the right queue order
        $job = $worker->reserve();
        $this->assertSame($queueHigh, $job->getOriginQueue());
        $job = $worker->reserve();
        $this->assertSame($queueMedium, $job->getOriginQueue());
        $job = $worker->reserve();
        $this->assertSame($queueLow, $job->getOriginQueue());
    }

    public function testWildcardQueueWorkerWorksAllQueues()
    {
        $queue = new Queue('notastar');
        $queue->setRedisClient($this->redis);

        $wildcardQueue = new WildcardQueue($this->redis);

        $worker = new Worker(
            $wildcardQueue
        );

        // Queue the jobs in a different order
        $queue->push(new Job('Test_Job_1'));

        $job = $worker->reserve();
        // The job should come from the original queue.
        $this->assertEquals($queue, $job->getOriginQueue());
    }

    public function testWorkerDoesNotWorkOnUnknownQueues()
    {
        $queueOne = new Queue('queue1');
        $queueOne->setRedisClient($this->redis);
        $queueTwo = new Queue('queue2');
        $queueTwo->setRedisClient($this->redis);

        $queueTwo->push(new Job('Test_Job'));

        $worker = new Worker($queueOne);
        $this->assertNull($worker->reserve());
    }

    public function testWorkerPerformSendsCorrectArgumentsToJobInstance()
    {
        $worker = new Worker();

        $args = array(
            1,
            array(
                'foo' => 'test'
            ),
            'key' => 'baz',
        );

        $job = new Job('Resque\Tests\Jobs\Simple', $args);

        $worker->perform($job);

        $this->assertSame(
            $args,
            Simple::$lastPerformArguments
        );
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
        $eventDispatcher = new EventDispatcher();
        $eventTriggered = 0;
        $eventDispatcher->addListener(
            $eventName,
            function () use (&$eventTriggered) {
                $eventTriggered++;
            }
        );

        $worker = new Worker(null, null, $eventDispatcher);

        $job = new Job($jobClass);
        $job->setOriginQueue(new Queue('foo'));

        $worker->perform($job);

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
            array(0, 'resque.job.before_perform', 'Resque\Tests\Jobs\NoPerformMethod'),
            array(0, 'resque.job.after_perform', 'Resque\Tests\Jobs\NoPerformMethod'),
            array(0, 'resque.job.performed', 'Resque\Tests\Jobs\NoPerformMethod'),
            array(1, 'resque.job.failed', 'Resque\Tests\Jobs\NoPerformMethod'),
            // Normal, expected to work job.
            array(1, 'resque.job.before_perform', 'Resque\Tests\Jobs\Simple'),
            array(1, 'resque.job.after_perform', 'Resque\Tests\Jobs\Simple'),
            array(1, 'resque.job.performed', 'Resque\Tests\Jobs\Simple'),
            array(0, 'resque.job.failed', 'Resque\Tests\Jobs\Simple'),
            // Job that will fail.
            array(1, 'resque.job.before_perform', 'Resque\Tests\Jobs\Failure'),
            array(0, 'resque.job.after_perform', 'Resque\Tests\Jobs\Failure'),
            array(0, 'resque.job.performed', 'Resque\Tests\Jobs\Failure'),
            array(1, 'resque.job.failed', 'Resque\Tests\Jobs\Failure'),
        );
    }

    public function testBeforePerformEventCanStopWork()
    {
        $eventDispatcher = new EventDispatcher();

        $eventDispatcher->addListener(
            'resque.job.before_perform',
            function () {
                throw new \Exception('Take a break');
            }
        );

        $eventTriggered = 0;
        $eventDispatcher->addListener(
            'resque.job.failed',
            function () use (&$eventTriggered) {
                $eventTriggered++;
            }
        );

        $worker = new Worker(null, null, $eventDispatcher);

        $job = new Job('Resque\Tests\Jobs\Simple');
        $job->setOriginQueue(new Queue('baz'));

        $this->assertFalse(
            $worker->perform($job),
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
        $eventDispatcher = new EventDispatcher();

        $eventTriggered = 0;
        $eventDispatcher->addListener(
            'resque.worker.before_fork',
            function () use (&$eventTriggered) {
                $eventTriggered++;
            }
        );

        $job = new Job('Resque\Tests\Jobs\Simple');

        $queue = new Queue('baz');
        $queue->setRedisClient($this->redis);
        $queue->push($job);

        $worker = new Worker($queue, null, $eventDispatcher);
        $worker->setRedisClient($this->redis);

        $worker->work(0);

        $this->assertEquals(1, $eventTriggered);
    }

    public function testWorkerTracksCurrentJobCorrectly()
    {
        $queue = new Queue('jobs');
        $queue->setRedisClient($this->redis);

        $job = new Job('Resque\Tests\Jobs\Simple');
        $queue->push($job);

        $mockWorker = $this->getMock(
            'Resque\Worker',
            array('workComplete'),
            array(array($queue))
        );
        $mockWorker
            ->expects($this->once())
            ->method('workComplete')
            ->will($this->returnValue(null));
        $mockWorker->setRedisClient($this->redis);
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
        $queue = new Queue('jobs');
        $queue->setRedisClient($this->redis);

        $job = new Job('Resque\Tests\Jobs\DirtyExit');
        $queue->push($job);

        $test = $this;
        $eventDispatcher = new EventDispatcher();
        $callbackTriggered = false;
        $eventDispatcher->addListener(
            'resque.job.failed',
            function ($event) use (&$callbackTriggered, $test) {
                $callbackTriggered = true;
                $test->assertInstanceOf('Resque\Job\Exception\DirtyExitException', $event->getException());
            }
        );

        $worker = new Worker($queue, null, $eventDispatcher);
        $worker->setRedisClient($this->redis);
        $worker->work(0);

        $this->assertTrue($callbackTriggered);
    }

    public function testPausedWorkerDoesNotPickUpJobs()
    {
        $queue = new Queue('jobs');
        $queue->setRedisClient($this->redis);
        $queue->push(new Job('Resque\Tests\Jobs\Simple'));

        $worker = new Worker($queue);
        $worker->pauseProcessing();

        $worker->work(0);

        $this->assertEquals(1, $queue->count());
    }

    public function testResumedWorkerPicksUpJobs()
    {
        return self::markTestSkipped();

        $worker = new Worker('*');
        $worker->setLogger(new Resque_Log());
        $worker->pauseProcessing();
        Resque::enqueue('jobs', 'Test_Job');
        $worker->work(0);
        $this->assertEquals(0, Resque_Stat::get('processed'));
        $worker->unPauseProcessing();
        $worker->work(0);
        $this->assertEquals(1, Resque_Stat::get('processed'));
    }

    public function testWorkerClearsItsStatusWhenNotWorking()
    {
        return self::markTestSkipped();

        Resque::enqueue('jobs', 'Test_Job');
        $worker = new Worker('jobs');
        $worker->setLogger(new Resque_Log());
        $job = $worker->reserve();
        $worker->workingOn($job);
        $worker->doneWorking();
        $this->assertEquals(array(), $worker->job());
    }

    public function testWorkerRecordsWhatItIsWorkingOn()
    {
        return self::markTestSkipped();

        $worker = new Worker('jobs');
        $worker->setLogger(new Resque_Log());
        $worker->registerWorker();

        $payload = array(
            'class' => 'Test_Job'
        );
        $job = new Resque_Job('jobs', $payload);
        $worker->workingOn($job);

        $job = $worker->job();
        $this->assertEquals('jobs', $job['queue']);
        if (!isset($job['run_at'])) {
            $this->fail('Job does not have run_at time');
        }
        $this->assertEquals($payload, $job['payload']);
    }

    public function testForkingCanBeDisabled()
    {
        $job = new Job('Resque\Tests\Jobs\Simple');

        $worker = $this->getMock(
            'Resque\Worker',
            array('perform', 'reserve'),
            array(null)
        );
        $worker->expects($this->at(0))->method('reserve')->will($this->returnValue($job));
        $worker->expects($this->at(1))->method('perform')->will($this->returnValue(null));
        $worker->expects($this->at(2))->method('reserve')->will($this->returnValue(null));

        $worker->setRedisClient($this->redis);
        $worker->setForkOnPerform(false);

        $worker->work(0); // This test fails if the worker forks, as perform is not marked as called in the parent
    }

    /**
     * @expectedException \Resque\Exception\ResqueRuntimeException
     */
    public function testCannotSetCurrentJobIfNotNull()
    {
        $worker = new Worker();
        $worker->setRedisClient($this->redis);

        $worker->setCurrentJob(new Job());
        $worker->setCurrentJob(new Job());
    }
}
