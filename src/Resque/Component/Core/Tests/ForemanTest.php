<?php

namespace Resque\Component\Core\Tests;

use PHPUnit_Framework_TestCase;
use Resque\Component\Core\Foreman;
use Resque\Component\System\StandardSystem;

class ForemanTest extends PHPUnit_Framework_TestCase
{
    public function testForkToWork()
    {
        $me = getmypid();
        $workerRegistry = $this->getMock('Resque\Component\Worker\Registry\WorkerRegistryInterface');
        $jobInstanceFactory = $this->getMock('Resque\Component\Job\Factory\JobInstanceFactoryInterface');
        $eventDispatcher = $this->getMock('Resque\Component\Core\Event\EventDispatcherInterface');
        // @todo move away from mocking.
        $foreman = new Foreman($workerRegistry, $eventDispatcher, new StandardSystem());

        $mockWorker = $this->getMock(
            'Resque\Component\Worker\Worker',
            array('work'),
            array($jobInstanceFactory, $eventDispatcher)
        );

        $mockWorker
            ->expects($this->any())
            ->method('work')
            ->will($this->returnValue(null));

        $workers = array(
            clone $mockWorker,
            clone $mockWorker,
            clone $mockWorker,
        );

        $foreman->work($workers, true);

        // Check the workers hold different PIDs
        foreach ($workers as $worker) {
            $this->assertNotEquals(0, $worker->getProcess()->getPid());
            $this->assertNotEquals($me, $worker->getProcess()->getPid());
        }
    }
}
