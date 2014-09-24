<?php

namespace Resque\Tests\Failure;

use Resque\Tests\ResqueTestCase;
use Resque\Failure\Redis;
use Resque\Job;
use Resque\Queue;
use Resque\Worker;

class RedisTest extends ResqueTestCase
{
    public function testCanSave()
    {
        $backend = new Redis($this->redis);

        $this->assertEquals(0, $backend->count());

        $job = new Job('derp');
        $worker = new Worker();

        $backend->save(
            $job,
            new \Exception('it broke'),
            new Queue('jobs'),
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
