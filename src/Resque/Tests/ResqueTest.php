<?php

namespace Resque\Tests;

use Resque\Resque;

class ResqueTest extends ResqueTestCase
{
    public function testCanCreateQueue()
    {
        $resque = new Resque($this->redis);
        $job = $resque->enqueue('foo', 'Resque\Tests\Jobs\Test');

        $this->assertInstanceOf('Resque\Job\JobInterface', $job);
        $this->assertNotNull($job->getId());
    }
}
