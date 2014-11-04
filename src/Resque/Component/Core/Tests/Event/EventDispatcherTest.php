<?php

namespace Resque\Component\Core\Tests\Event;

use Resque\Component\Core\Event\EventDispatcher;

class EventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testEventsCanBeDispatched()
    {
        $dispatcher = new EventDispatcher();

        $mockEvent = $this->getMock('Resque\Event\EventInterface');
        $mockEvent
            ->expects($this->atLeastOnce())
            ->method('getName')
            ->will(
                $this->returnValue('test')
            );

        $called = 0;

        $dispatcher->addListener(
            'test',
            function () use (&$called) {
                $called++;
            }
        );

        $dispatcher->dispatch($mockEvent);
        $this->assertEquals(1, $called);

        $dispatcher->dispatch($mockEvent);
        $this->assertEquals(2, $called);
    }

    public function testListenersCanBeAddedAndRemoved()
    {
        $dispatcher = new EventDispatcher();

        $this->assertCount(0, $dispatcher->getListeners());

        $test = $this;
        $callable = function () use ($test) {
            $test->fail('Did not expect listeners to be called.');
        };

        $dispatcher->addListener(
            'foo',
            $callable
        );

        $this->assertCount(1, $dispatcher->getListeners());

        $dispatcher->removeListener('foo', $callable);
        $dispatcher->removeListener('foo', $callable);

        $this->assertCount(0, $dispatcher->getListeners());

        $dispatcher->addListener(
            'bar',
            $callable
        );

        $this->assertCount(1, $dispatcher->getListeners());

        $dispatcher->clearListeners();

        $this->assertCount(0, $dispatcher->getListeners());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNonCallableThrowInvalidArgException()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('baz', array());
    }
}
