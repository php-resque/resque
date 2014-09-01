<?php

namespace Resque\Tests;

use Resque\Job;
use Resque\Resque;
use Resque\Foreman;
use Resque\Queue;
use Resque\Worker;

class StatTest extends ResqueTestCase
{
	public function testStatCanBeIncremented()
	{
        return self::markTestSkipped();

        Resque_Stat::incr('test_incr');
		Resque_Stat::incr('test_incr');
		$this->assertEquals(2, $this->redis->get('resque:stat:test_incr'));
	}

	public function testStatCanBeIncrementedByX()
	{
        return self::markTestSkipped();

        Resque_Stat::incr('test_incrX', 10);
		Resque_Stat::incr('test_incrX', 11);
		$this->assertEquals(21, $this->redis->get('resque:stat:test_incrX'));
	}

	public function testStatCanBeDecremented()
	{
        return self::markTestSkipped();

        Resque_Stat::incr('test_decr', 22);
		Resque_Stat::decr('test_decr');
		$this->assertEquals(21, $this->redis->get('resque:stat:test_decr'));
	}

	public function testStatCanBeDecrementedByX()
	{
        return self::markTestSkipped();

        Resque_Stat::incr('test_decrX', 22);
		Resque_Stat::decr('test_decrX', 11);
		$this->assertEquals(11, $this->redis->get('resque:stat:test_decrX'));
	}

	public function testGetStatByName()
	{
        return self::markTestSkipped();

        Resque_Stat::incr('test_get', 100);
		$this->assertEquals(100, Resque_Stat::get('test_get'));
	}

	public function testGetUnknownStatReturns0()
	{
        return self::markTestSkipped();

        $this->assertEquals(0, Resque_Stat::get('test_get_unknown'));
	}
}
