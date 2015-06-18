<?php

namespace spec\Resque\Component\Core\Event;

use Exception;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EventDispatcherSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Resque\Component\Core\Event\EventDispatcher');
    }

    public function it_is_an_event_dispatcher()
    {
        $this->shouldImplement('Resque\Component\Core\Event\EventDispatcherInterface');
    }

    public function it_has_no_listeners_by_default()
    {
        $this->getListeners()->shouldHaveCount(0);
    }

    public function it_accepts_listeners()
    {
        $callable = function() {
            throw new Exception('Did not expect listeners to be called');
        };

        $this->addListener('foo', $callable);

        $this->getListeners()->shouldHaveCount(1);
        $this->getListeners()->shouldHaveKey('foo');
        $this->getListeners('foo')->shouldHaveCount(1);
        $this->getListeners('foo')->shouldContain($callable);
    }

    public function it_rejects_listeners_that_are_not_callable()
    {
        $this->shouldThrow('InvalidArgumentException')->during('addListener', array('baz', 1234));
    }

    public function it_can_remove_a_specific_listener()
    {
        $callable = function() {
            throw new Exception('Did not expect listeners to be called');
        };
        $this->addListener('foo', function() {});
        $this->addListener('bar', $callable);
        $this->addListener('baz', $callable);
        $this->getListeners()->shouldHaveCount(3);
        $this->removeListener('bar', $callable);
        $this->getListeners()->shouldHaveCount(2);
        $this->getListeners('bar')->shouldHaveCount(0);
    }

    public function it_can_clear_all_listeners()
    {
        $this->addListener('bar', function(){});
        $this->getListeners()->shouldHaveCount(1);
        $this->clearListeners();
        $this->getListeners()->shouldHaveCount(0);
    }

    public function it_dispatches_an_event_when_there_are_no_listeners()
    {
        $this->dispatch('example');
    }

    public function it_dispatches_an_event_to_all_registered_listeners()
    {
        $called = 0;
        $callable = function () use (&$called) {
            $called++;
        };

        $this->addListener('created', $callable);
        $this->dispatch('created');

        if (1 != $called) {
            throw new Exception('Listener expected to be called');
        }

        $this->dispatch('created', new \stdClass());

        if (2 != $called) {
            throw new Exception('Listener expected to be called');
        }

        $this->addListener('created', $callable);
        $this->dispatch('created');

        if (4 != $called) {
            throw new Exception('Listener expected to be called');
        }
    }
}
