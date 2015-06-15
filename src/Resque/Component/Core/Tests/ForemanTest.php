<?php

namespace Resque\Component\Core\Tests;

use Resque\Component\Core\Foreman;
use Resque\Redis\RedisWorkerRegistry;
use Resque\Component\Core\Test\ResqueTestCase;
use Resque\Component\Worker\Factory\WorkerFactory;

class ForemanTest extends ResqueTestCase
{
    /**
     * @var Foreman
     */
    protected $foreman;

    /**
     * @var \Resque\Redis\RedisWorkerRegistry
     */
    protected $workerRegistry;

    public function testForking()
    {

        $this->workerRegistry = new RedisWorkerRegistry($this->redis, $this->getMock('Resque\Component\Worker\Factory\WorkerFactoryInterface'));
        $this->foreman = new Foreman($this->workerRegistry);


        function it_starts_workers(
            WorkerInterface $worker1,
            WorkerInterface $worker2,
            WorkerInterface $worker3,
            EventDispatcherInterface $eventDispatcher
        ) {
            $eventDispatcher->dispatch(ResqueEvents::BEFORE_FORK, null)->shouldBeCalled(3);

            $worker1->work()->shouldBeCalled();
            $worker1->setProcess(Argument::type('Resque\Component\Core\Process'))->shouldBeCalled();
            $worker1->getId()->shouldBeCalled()->willReturn('earth:1:lunch');
            $worker2->work()->shouldBeCalled();
            $worker2->setProcess(Argument::type('Resque\Component\Core\Process'))->shouldBeCalled();
            $worker2->getId()->shouldBeCalled()->willReturn('earth:2:high');
            $worker3->work()->shouldBeCalled();
            $worker3->setProcess(Argument::type('Resque\Component\Core\Process'))->shouldBeCalled();
            $worker3->getId()->shouldBeCalled()->willReturn('earth:3:test');

            $workers = array(
                $worker1,
                $worker2,
                $worker3
            );

            $this->work($workers);
        }

        $me = getmypid();

        $mockWorker = $this->getMock(
            'Resque\Component\Worker\Worker',
            array('work'),
            array(array())
        );
        $mockWorker
            ->expects($this->any())
            ->method('work')
            ->will($this->returnValue(null));

        $workers = array(
            clone $mockWorker,
            clone $mockWorker,
            clone $mockWorker,
            clone $mockWorker,
            clone $mockWorker,
        );

        $this->foreman->work($workers, true);

        // Check the workers hold different PIDs
        foreach ($workers as $worker) {
            $this->assertNotEquals(0, $worker->getProcess()->getPid());
            $this->assertNotEquals($me, $worker->getProcess()->getPid());
        }
    }
}
