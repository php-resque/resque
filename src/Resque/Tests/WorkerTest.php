<?php

namespace Resque\Tests;

use Resque\Job;
use Resque\Resque;
use Resque\Foreman;
use Resque\Queue;
use Resque\Worker;

class WorkerTest extends ResqueTestCase
{
    public function testSetId()
    {
        $worker = new Worker();
        $worker->setId('fiddle-sticks');
        $this->assertSame('fiddle-sticks', (string)$worker);
    }

	public function testPausedWorkerDoesNotPickUpJobs()
	{
        return self::markTestSkipped();

        $worker = new Worker('*');
		$worker->setLogger(new Resque_Log());
		$worker->pauseProcessing();
		Resque::enqueue('jobs', 'Test_Job');
		$worker->work(0);
		$worker->work(0);
		$this->assertEquals(0, Resque_Stat::get('processed'));
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

	public function testWorkerCanWorkOverMultipleQueues()
	{
        $queueOne = new Queue('queue1');
        $queueOne->setRedisBackend($this->redis);
        $queueTwo = new Queue('queue2');
        $queueTwo->setRedisBackend($this->redis);

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
		$this->assertEquals($queueOne, $job->queue);

		$job = $worker->reserve();
		$this->assertEquals($queueTwo, $job->queue);
	}

	public function testWorkerWorksQueuesInSpecifiedOrder()
	{
        $queueHigh = new Queue('high');
        $queueHigh->setRedisBackend($this->redis);
        $queueMedium = new Queue('medium');
        $queueMedium->setRedisBackend($this->redis);
        $queueLow = new Queue('low');
        $queueLow->setRedisBackend($this->redis);

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
		$this->assertSame($queueHigh, $job->queue);
		$job = $worker->reserve();
		$this->assertSame($queueMedium, $job->queue);
		$job = $worker->reserve();
		$this->assertSame($queueLow, $job->queue);
	}

	public function testWildcardQueueWorkerWorksAllQueues()
	{
        return self::markTestSkipped();

        $worker = new Worker('*');

		Resque::enqueue('queue1', 'Test_Job_1');
		Resque::enqueue('queue2', 'Test_Job_2');

		$job = $worker->reserve();
		$this->assertEquals('queue1', $job->queue);

		$job = $worker->reserve();
		$this->assertEquals('queue2', $job->queue);
	}

	public function testWorkerDoesNotWorkOnUnknownQueues()
	{
        $queueOne = new Queue('queue1');
        $queueOne->setRedisBackend($this->redis);
        $queueTwo = new Queue('queue2');
        $queueTwo->setRedisBackend($this->redis);

        $queueTwo->push(new Job('Test_Job'));

		$worker = new Worker($queueOne);
		$this->assertNull($worker->reserve());
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
		if(!isset($job['run_at'])) {
			$this->fail('Job does not have run_at time');
		}
		$this->assertEquals($payload, $job['payload']);
	}

	public function testWorkerErasesItsStatsWhenShutdown()
	{
        return self::markTestSkipped();

        $queue = new Queue('jobs');
        $queue->setRedisBackend($this->redis);

        $queue->push(new Job('Resque\Tests\Job\Simple'));
        $queue->push(new Job('Invalid_Job'));

		$worker = new Worker($queue);
        $worker->setRedisBackend($this->redis);
        $worker->setForkOnPerform(false);

		$worker->work(0);

        $this->assertEquals(1, $worker->getStat('processed'));

		$worker->work(0);

//		$this->assertEquals(0, $worker->getStat('processed'));
//		$this->assertEquals(0, $worker->getStat('failed'));
	}

	public function testWorkerFailsUncompletedJobsOnExit()
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
		$worker->unregisterWorker();

		$this->assertEquals(1, Resque_Stat::get('failed'));
	}

    public function testBlockingListPop()
    {
        return self::markTestSkipped();

        $worker = new Worker('jobs');
		$worker->setLogger(new Resque_Log());
        $worker->registerWorker();

        Resque::enqueue('jobs', 'Test_Job_1');
        Resque::enqueue('jobs', 'Test_Job_2');

        $i = 1;
        while($job = $worker->reserve(true, 1))
        {
            $this->assertEquals('Test_Job_' . $i, $job->payload['class']);

            if($i == 2) {
                break;
            }

            $i++;
        }

        $this->assertEquals(2, $i);
    }
}
