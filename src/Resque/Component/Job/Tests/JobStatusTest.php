<?php

namespace Resque\Tests\Job;

use Resque\Component\Core\Resque;
use Resque\Redis\Test\ResqueTestCase;

class JobStatusTest extends ResqueTestCase
{
    public function setUp()
    {
        parent::setUp();

        // Register a worker to test with
//		$this->worker = new Resque_Worker('jobs');
//		$this->worker->setLogger(new Resque_Log());
    }

    public function testJobStatusCanBeTracked()
    {
        return self::markTestSkipped();

        $token = Resque::enqueue('jobs', 'Test_Job', null, true);
        $status = new Resque_Job_Status($token);
        $this->assertTrue($status->isTracking());
    }

    public function testJobStatusIsReturnedViaJobInstance()
    {
        return self::markTestSkipped();

        $token = Resque::enqueue('jobs', 'Test_Job', null, true);
        $job = Resque_Job::reserve('jobs');
        $this->assertEquals(Resque_Job_Status::STATUS_WAITING, $job->getStatus());
    }

    public function testQueuedJobReturnsQueuedStatus()
    {
        return self::markTestSkipped();

        $token = Resque::enqueue('jobs', 'Test_Job', null, true);
        $status = new Resque_Job_Status($token);
        $this->assertEquals(Resque_Job_Status::STATUS_WAITING, $status->get());
    }

    public function testRunningJobReturnsRunningStatus()
    {
        return self::markTestSkipped();

        $token = Resque::enqueue('jobs', 'Failing_Job', null, true);
        $job = $this->worker->reserve();
        $this->worker->workingOn($job);
        $status = new Resque_Job_Status($token);
        $this->assertEquals(Resque_Job_Status::STATUS_RUNNING, $status->get());
    }

    public function testFailedJobReturnsFailedStatus()
    {
        return self::markTestSkipped();


        $token = Resque::enqueue('jobs', 'Failing_Job', null, true);
        $this->worker->work(0);
        $status = new Resque_Job_Status($token);
        $this->assertEquals(Resque_Job_Status::STATUS_FAILED, $status->get());
    }

    public function testCompletedJobReturnsCompletedStatus()
    {
        return self::markTestSkipped();


        $token = Resque::enqueue('jobs', 'Test_Job', null, true);
        $this->worker->work(0);
        $status = new Resque_Job_Status($token);
        $this->assertEquals(Resque_Job_Status::STATUS_COMPLETE, $status->get());
    }

    public function testStatusIsNotTrackedWhenToldNotTo()
    {
        return self::markTestSkipped();


        $token = Resque::enqueue('jobs', 'Test_Job', null, false);
        $status = new Resque_Job_Status($token);
        $this->assertFalse($status->isTracking());
    }

    public function testStatusTrackingCanBeStopped()
    {
        return self::markTestSkipped();


        Resque_Job_Status::create('test');
        $status = new Resque_Job_Status('test');
        $this->assertEquals(Resque_Job_Status::STATUS_WAITING, $status->get());
        $status->stop();
        $this->assertFalse($status->get());
    }

    public function testRecreatedJobWithTrackingStillTracksStatus()
    {
        return self::markTestSkipped();


        $originalToken = Resque::enqueue('jobs', 'Test_Job', null, true);
        $job = $this->worker->reserve();

        // Mark this job as being worked on to ensure that the new status is still
        // waiting.
        $this->worker->workingOn($job);

        // Now recreate it
        $newToken = $job->recreate();

        // Make sure we've got a new job returned
        $this->assertNotEquals($originalToken, $newToken);

        // Now check the status of the new job
        $newJob = Resque_Job::reserve('jobs');
        $this->assertEquals(Resque_Job_Status::STATUS_WAITING, $newJob->getStatus());
    }
}