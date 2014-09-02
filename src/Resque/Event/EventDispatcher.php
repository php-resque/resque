<?php

namespace Resque\Event;

/**
 * Resque simple event dispatcher
 *
 * Allows simple binding of callables to events. @see http://php.net/manual/en/language.types.callable.php
 *
 * It is expected that you'll inject your own EventDispatcher, and manage the events in a way
 * that makes sense to you. This class is not intended to solve any event dispatching problems for you.
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array Array containing all registered callbacks, indexed by event name.
     */
    protected $listeners = array();

    /**
     * Dispatch an Event
     *
     * @param EventInterface $event The event to dispatch to relevant listeners.
     */
    public function dispatch(EventInterface $event)
    {
        if (false === isset($this->listeners[$event->getName()])) {

            return;
        }

        foreach ($this->listeners[$event->getName()] as $callback) {
            if (false === is_callable($callback)) {
                continue;
            }

            call_user_func($callback, $event);
        }
    }

    /**
     * Listen in on a given event to have a specified callback fired.
     *
     * @throws \InvalidArgumentException when $callback is not a callable.
     *
     * @param string $eventName The name of the event to listen for.
     * @param callable $callback Any callback callable by call_user_func_array.
     */
    public function addListener($eventName, $callback)
    {
        if (false === is_callable($callback)) {
            throw new \InvalidArgumentException('$callback must be callable');
        }

        if (false === isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = array();
        }

        $this->listeners[$eventName][] = $callback;
    }

    /**
     * Stop a given callback from listening on a specific event.
     *
     * @param string $eventName The name of the event to stop listening for.
     * @param mixed $callback The callback as defined when addListener() was called.
     */
    public function removeListener($eventName, $callback)
    {
        if (false === isset($this->listeners[$eventName])) {

            return;
        }

        $key = array_search($callback, $this->listeners[$eventName]);
        if ($key !== false) {
            unset($this->listeners[$eventName][$key]);

            if (0 === count($this->listeners[$eventName])) {
                unset($this->listeners[$eventName]);
            }
        }
    }

    /**
     * Get registered listeners.
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * Call all registered listeners.
     */
    public function clearListeners()
    {
        $this->listeners = array();
    }
}
