<?php
// Somewhere in our application, we need to register:
Resque_Event::listen('afterEnqueue', array('My_Resque_Plugin', 'afterEnqueue'));
Resque_Event::listen('beforeFirstFork', array('My_Resque_Plugin', 'beforeFirstFork'));
Resque_Event::listen('beforeFork', array('My_Resque_Plugin', 'beforeFork'));
Resque_Event::listen('afterFork', array('My_Resque_Plugin', 'afterFork'));
Resque_Event::listen('beforePerform', array('My_Resque_Plugin', 'beforePerform'));
Resque_Event::listen('afterPerform', array('My_Resque_Plugin', 'afterPerform'));
Resque_Event::listen('onFailure', array('My_Resque_Plugin', 'onFailure'));

class My_Resque_Plugin
{
	public function afterEnqueue($class, $arguments)
	{
		echo "Job was queued for " . $class . ". Arguments:";
		print_r($arguments);
	}
	
	public function beforeFirstFork($worker)
	{
		echo "Worker started. Listening on queues: " . implode(', ', $worker->queues(false)) . "\n";
	}
	
	public function beforeFork($job)
	{
		echo "Just about to fork to run " . $job;
	}
	
	public function afterFork($job)
	{
		echo "Forked to run " . $job . ". This is the child process.\n";
	}
	
	public function beforePerform($job)
	{
		echo "Cancelling " . $job . "\n";
	//	throw new Resque_Job_DontPerform;
	}
	
	public function afterPerform($job)
	{
		echo "Just performed " . $job . "\n";
	}
	
	public function onFailure($exception, $job)
	{
		echo $job . " threw an exception:\n" . $exception;
	}
}