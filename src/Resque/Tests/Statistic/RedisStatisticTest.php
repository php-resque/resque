<?php

namespace Resque\Tests\Statistic;

use Resque\Tests\ResqueTestCase;
use Resque\Statistic\RedisStatistic;

class RedisStatisticTest extends ResqueTestCase
{
    /**
     * @var RedisStatistic
     */
    protected $stat;

    public function setUp()
    {
        parent::setUp();

        $this->stat = new RedisStatistic($this->redis);
    }

    public function testStatCanBeIncremented()
    {
        $this->stat->increment('incr');
        $this->stat->increment('incr');
        $this->assertEquals(2, $this->redis->get('stat:incr'));
    }

    public function testStatCanBeIncrementedByX()
    {
        $this->stat->increment('incrx', 10);
        $this->stat->increment('incrx', 11);
        $this->assertEquals(21, $this->redis->get('stat:incrx'));
    }

    public function testStatCanBeDecremented()
    {
        $this->stat->increment('decr', 22);
        $this->stat->decrement('decr');
        $this->assertEquals(21, $this->redis->get('stat:decr'));
    }

    public function testStatCanBeDecrementedByX()
    {
        $this->stat->increment('decrx', 22);
        $this->stat->decrement('decrx', 11);
        $this->assertEquals(11, $this->redis->get('stat:decrx'));
    }

    public function testGetStatByName()
    {
        $this->stat->increment('test', 100);
        $this->assertEquals(100, $this->stat->get('test'));
    }

    public function testGetUnknownStatReturns0()
    {
        $this->assertEquals(0, $this->stat->get('unknown'));
    }

    public function testClearRemovesKey()
    {
        $this->stat->increment('test', 1);
        $this->assertTrue($this->redis->exists('stat:test'));
        $this->stat->clear('test');
        $this->assertFalse($this->redis->exists('stat:test'));
    }
}
