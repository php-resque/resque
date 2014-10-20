<?php

namespace Resque\Tests\Statistic;

use Resque\Statistic\BlackHoleStatistic;

class BlackHoleStatisticTest extends \PHPUnit_Framework_TestCase
{
    public function testBlackHoleBackend()
    {
        $stat = new BlackHoleStatistic();
        $this->assertTrue($stat->increment('test', 10));
        $this->assertTrue($stat->decrement('test'));
        $this->assertNull($stat->get('test'));
        $this->assertTrue($stat->clear('test'));
    }
}
