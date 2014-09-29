<?php

namespace Resque\Tests\Event;

use Resque\Event\WorkerAfterForkEvent;

class WorkerAfterForkEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $worker = $this->getMock('Resque\WorkerInterface');
        $job = $this->getMock('Resque\Job\JobInterface');
        $event = new WorkerAfterForkEvent($worker, $job);
        $this->assertEquals($worker, $event->getWorker());
        $this->assertEquals($job, $event->getJob());
    }
}
