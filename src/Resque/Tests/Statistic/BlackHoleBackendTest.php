<?php

namespace Resque\Tests\Statistic;

use Resque\Statistic\BlackHoleBackend;

class BlackHoleBackendTest extends \PHPUnit_Framework_TestCase
{
    public function testBlackHoleBackend()
    {
        $stat = new BlackHoleBackend();
        $this->assertTrue($stat->increment('test', 10));
        $this->assertTrue($stat->decrement('test'));
        $this->assertNull($stat->get('test'));
        $this->assertTrue($stat->clear('test'));
    }
}
