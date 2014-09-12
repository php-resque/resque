<?php

namespace Resque\Tests;

use Resque\Job;
use Resque\Resque;
use Resque\Foreman;
use Resque\Queue;
use Resque\Worker;

class EventTest extends ResqueTestCase
{
	private $callbacksHit = array();
	
	public function setUp()
	{
//		Test_Job::$called = false;
//
//		// Register a worker to test with
//		$this->worker = new \Resque\Worker(new \Resque\Foreman(), 'jobs');
//		//$this->worker->setLogger(new Resque_Log());
//		//$this->worker->registerWorker();
	}

	public function tearDown()
	{
        return self::markTestSkipped();

        \Resque\Event::clearListeners();
		$this->callbacksHit = array();
	}

	public function getEventTestJob()
	{
        return self::markTestSkipped();

        $payload = array(
			'class' => 'Test_Job',
			'args' => array(
				'somevar',
			),
		);
		$job = new Resque_Job('jobs', $payload);
		$job->worker = $this->worker;
		return $job;
	}

	public function testBeforeForkEventCallbackFires()
	{
        return self::markTestSkipped();

        $event = 'beforeFork';
		$callback = 'beforeForkEventCallback';

		\Resque\Event::listen($event, array($this, $callback));
		Resque::enqueue('jobs', 'Test_Job', array(
			'somevar'
		));
		$job = $this->getEventTestJob();
		$this->worker->work(0);
		$this->assertContains($callback, $this->callbacksHit, $event . ' callback (' . $callback .') was not called');
	}

	public function testBeforePerformEventCanStopWork()
	{
        return self::markTestSkipped();

        $callback = 'beforePerformEventDontPerformCallback';
		\Resque\Event::listen('beforePerform', array($this, $callback));

		$job = $this->getEventTestJob();

		$this->assertFalse($job->perform());
		$this->assertContains($callback, $this->callbacksHit, $callback . ' callback was not called');
		$this->assertFalse(Test_Job::$called, 'Job was still performed though Resque_Job_DontPerform was thrown');
	}
	
	public function testAfterEnqueueEventCallbackFires()
	{
        return self::markTestSkipped();

        $callback = 'afterEnqueueEventCallback';
		$event = 'afterEnqueue';
	
		\Resque\Event::listen($event, array($this, $callback));
		Resque::enqueue('jobs', 'Test_Job', array(
			'somevar'
		));	
		$this->assertContains($callback, $this->callbacksHit, $event . ' callback (' . $callback .') was not called');
	}

	public function testStopListeningRemovesListener()
	{
        return self::markTestSkipped();

        $callback = 'beforePerformEventCallback';
		$event = 'beforePerform';

		\Resque\Event::listen($event, array($this, $callback));
		\Resque\Event::stopListening($event, array($this, $callback));

		$job = $this->getEventTestJob();
		$this->worker->perform($job);
		$this->worker->work(0);

		$this->assertNotContains($callback, $this->callbacksHit, 
			$event . ' callback (' . $callback .') was called though \Resque\Event::stopListening was called'
		);
	}

	
	public function beforePerformEventDontPerformCallback($instance)
	{
        return self::markTestSkipped();

        $this->callbacksHit[] = __FUNCTION__;
		throw new Resque_Job_DontPerform;
	}
	
	public function assertValidEventCallback($function, $job)
	{
		$this->callbacksHit[] = $function;
		if (!$job instanceof Resque_Job) {
			$this->fail('Callback job argument is not an instance of Job');
		}
		$args = $job->getArguments();
		$this->assertEquals($args[0], 'somevar');
	}
	
	public function afterEnqueueEventCallback($class, $args)
	{
		$this->callbacksHit[] = __FUNCTION__;
		$this->assertEquals('Test_Job', $class);
		$this->assertEquals(array(
			'somevar',
		), $args);
	}
	
	public function beforePerformEventCallback($job)
	{
		$this->assertValidEventCallback(__FUNCTION__, $job);
	}
	
	public function afterPerformEventCallback($job)
	{
		$this->assertValidEventCallback(__FUNCTION__, $job);
	}

	public function beforeForkEventCallback($job)
	{
		$this->assertValidEventCallback(__FUNCTION__, $job);
	}
	
	public function afterForkEventCallback($job)
	{
		$this->assertValidEventCallback(__FUNCTION__, $job);
	}
}
