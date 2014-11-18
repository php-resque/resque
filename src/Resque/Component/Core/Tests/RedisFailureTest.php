<?php

namespace Resque\Component\Core\Tests;

use Resque\Component\Core\RedisFailure;
use Resque\Component\Core\RedisQueue;
use Resque\Component\Core\Test\ResqueTestCase;
use Resque\Component\Job\Model\Job;
use Resque\Component\Worker\Worker;

class RedisFailureTest extends ResqueTestCase
{
    public function testCanSave()
    {
        $backend = new RedisFailure($this->redis);

        $this->assertEquals(0, $backend->count());

        $job = new Job('derp');
        $queue = new RedisQueue($this->redis);
        $queue->setName('jobs');
        $job->setOriginQueue($queue);
        $worker = new Worker();

        $backend->save(
            $job,
            new \Exception('it broke'),
            $worker
        );

        $this->assertEquals(1, $backend->count());
        $this->assertTrue($this->redis->exists('failed'));

        $failure = json_decode($this->redis->lindex('failed', 0));
        $this->assertSame('Exception', $failure->exception);
        $this->assertSame('it broke', $failure->error);
        $this->assertSame($worker->getId(), $failure->worker);
        $this->assertSame('jobs', $failure->queue);

        $backend->clear();
        $this->assertEquals(0, $backend->count());
        $this->assertFalse($this->redis->exists('failed'));
    }
}
