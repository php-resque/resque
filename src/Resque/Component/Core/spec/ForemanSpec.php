<?php

namespace spec\Resque\Component\Core;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Core\Process;
use Resque\Component\core\ResqueEvents;
use Resque\Component\Worker\Model\WorkerInterface;
use Resque\Component\Worker\Registry\WorkerRegistryInterface;

class ForemanSpec extends ObjectBehavior
{
    function let(
        WorkerRegistryInterface $workerRegistry,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->beConstructedWith($workerRegistry, $eventDispatcher);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Core\Foreman');
    }

    // $this->work is tested with phpunit, due to process forking. I can't work out how to spec it in phpspec.
    // @see Resque\Component\Core\Tests\ForemanTest

    function it_cleans_up_dead_workers(
        WorkerRegistryInterface $workerRegistry,
        WorkerInterface $worker,
        WorkerInterface $deadWorker1,
        WorkerInterface $deadWorker2,
        Process $workerProcess,
        Process $deadWorker1Process,
        Process $deadWorker2Process
    ) {
        $hostname = (function_exists('gethostname')) ? gethostname() : php_uname('n'); // @todo fix hostname fetching
        $workerRegistry->all()->shouldBeCalled()->willReturn(array($worker, $deadWorker1, $deadWorker2));

        $worker->getHostname()->shouldBeCalled()->willReturn($hostname);
        $worker->getProcess()->shouldBeCalled()->willReturn($workerProcess);
        $workerProcess->getPid()->shouldBeCalled()->willReturn(getmypid());

        $deadWorker1->getHostname()->shouldBeCalled()->willReturn($hostname);
        $deadWorker1->getProcess()->shouldBeCalled()->willReturn($deadWorker1Process);
        $deadWorker1Process->getPid()->shouldBeCalled()->willReturn('1');

        $deadWorker2->getHostname()->shouldBeCalled()->willReturn($hostname);
        $deadWorker2->getProcess()->shouldBeCalled()->willReturn($deadWorker2Process);
        $deadWorker2Process->getPid()->shouldBeCalled()->willReturn('2');

        $workerRegistry->deregister($deadWorker1)->shouldBeCalled();
        $workerRegistry->deregister($deadWorker2)->shouldBeCalled();
        $workerRegistry->deregister($worker)->shouldNotBeCalled();

        $this->pruneDeadWorkers();
    }

    function it_does_not_clean_up_workers_on_another_host(
        $workerRegistry,
        WorkerInterface $localWorker,
        WorkerInterface $remoteWorker,
        Process $localProcess,
        Process $remoteProcess
    ) {
        $workerRegistry->all()->shouldBeCalled()->willReturn([$localWorker, $remoteWorker]);

        $localWorker->getProcess()->shouldBeCalled()->willReturn($localProcess);
        $remoteWorker->getProcess()->shouldBeCalled()->willReturn($remoteProcess);

        $localWorker->getHostname()->shouldBeCalled()->willReturn((function_exists('gethostname')) ? gethostname() : php_uname('n')); // @todo fix hostname fetching
        $remoteWorker->getHostname()->shouldBeCalled()->willReturn('my.other.host');

        $localProcess->getPid()->willReturn(1);
        $remoteProcess->getPid()->willReturn(1);

        $workerRegistry->deregister($localWorker)->shouldBeCalled();
        $workerRegistry->deregister($remoteWorker)->shouldNotBeCalled();

        $this->pruneDeadWorkers();
    }
}
