<?php

namespace Resque\Tests;

use Resque\Job;
use Resque\Resque;
use Resque\Foreman;
use Resque\Queue;
use Resque\Worker;

class LogTest extends ResqueTestCase
{
	public function testLogInterpolate()
	{
        return self::markTestSkipped();

        $logger   = new Resque_Log();
		$actual   = $logger->interpolate('string {replace}', array('replace' => 'value'));
		$expected = 'string value';

		$this->assertEquals($expected, $actual);
	}

	public function testLogInterpolateMutiple()
	{
        return self::markTestSkipped();

        $logger   = new Resque_Log();
		$actual   = $logger->interpolate(
			'string {replace1} {replace2}',
			array('replace1' => 'value1', 'replace2' => 'value2')
		);
		$expected = 'string value1 value2';

		$this->assertEquals($expected, $actual);
	}
}
