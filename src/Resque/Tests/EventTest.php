<?php

namespace Resque\Tests;

use Resque\Component\Core\Resque;
use Resque\Component\Core\Test\ResqueTestCase;

class EventTest extends ResqueTestCase
{
    public function testAfterEnqueueEventCallbackFires()
    {
        return self::markTestSkipped();

        $callback = 'afterEnqueueEventCallback';
        $event = 'afterEnqueue';

        \Resque\Event::listen($event, array($this, $callback));
        Resque::enqueue(
            'jobs',
            'Test_Job',
            array(
                'somevar'
            )
        );
        $this->assertContains($callback, $this->callbacksHit, $event . ' callback (' . $callback . ') was not called');
    }

    public function afterEnqueueEventCallback($class, $args)
    {
        $this->callbacksHit[] = __FUNCTION__;
        $this->assertEquals('Test_Job', $class);
        $this->assertEquals(
            array(
                'somevar',
            ),
            $args
        );
    }
}
