<?php

namespace Resque\Tests;

use Resque\Job;
use Resque\Resque;

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

    public function beforePerformEventDontPerformCallback($instance)
    {
        return self::markTestSkipped();

        $this->callbacksHit[] = __FUNCTION__;
        throw new Resque_Job_DontPerform;
    }

    public function assertValidEventCallback($function, $job)
    {
        $this->callbacksHit[] = $function;
        if (!$job instanceof Resque_Job) {
            $this->fail('Callback job argument is not an instance of Job');
        }
        $args = $job->getArguments();
        $this->assertEquals($args[0], 'somevar');
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

    public function beforePerformEventCallback($job)
    {
        $this->assertValidEventCallback(__FUNCTION__, $job);
    }

    public function afterPerformEventCallback($job)
    {
        $this->assertValidEventCallback(__FUNCTION__, $job);
    }

    public function beforeForkEventCallback($job)
    {
        $this->assertValidEventCallback(__FUNCTION__, $job);
    }

    public function afterForkEventCallback($job)
    {
        $this->assertValidEventCallback(__FUNCTION__, $job);
    }
}
