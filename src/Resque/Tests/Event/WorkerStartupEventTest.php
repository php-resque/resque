<?php

namespace Resque\Tests\Event;

use Resque\Event\WorkerStartupEvent;

class WorkerStartupEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $worker = $this->getMock('Resque\WorkerInterface');
        $event = new WorkerStartupEvent($worker);
        $this->assertEquals($worker, $event->getWorker());
    }
}
