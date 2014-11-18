<?php

namespace Resque\Component\Core\Tests\Event;

use Resque\Component\Core\Event\EventDispatcher;

class EventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    const MOCK_EVENT = 'mock.event';

    public function testEventsCanBeDispatched()
    {
        $dispatcher = new EventDispatcher();

        $mockEvent = $this->getMock('Resque\Event\EventInterface');

        $called = 0;

        $dispatcher->addListener(
            self::MOCK_EVENT,
            function () use (&$called) {
                $called++;
            }
        );

        $dispatcher->dispatch(self::MOCK_EVENT, $mockEvent);
        $this->assertEquals(1, $called);

        $dispatcher->dispatch(self::MOCK_EVENT, $mockEvent);
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
