<?php

use Resque\Resque;

/**
 * Resque test bootstrap file - sets up a test environment.
 *
 * @package		Resque/Tests
 * @author		Chris Boulton <chris@bigcommerce.com>
 * @license		http://www.opensource.org/licenses/mit-license.php
 */

$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->add('Resque_Tests', __DIR__);
$loader->add('Resque\Tests', __DIR__);

define('TEST_MISC', realpath(__DIR__ . '/misc/'));
define('REDIS_CONF', TEST_MISC . '/redis.conf');

// Get redis port from conf
$config = file_get_contents(REDIS_CONF);
if(!preg_match('#^\s*port\s+([0-9]+)#m', $config, $matches)) {
	echo "Could not determine redis port from redis.conf";
	exit(1);
}

Resque::setBackend('localhost:' . $matches[1]);

if(function_exists('pcntl_signal')) {
	// Override INT and TERM signals, so they do a clean shutdown and also
	// clean up redis-server as well.
	function sigint()
	{
	 	exit;
	}
	pcntl_signal(SIGINT, 'sigint');
	pcntl_signal(SIGTERM, 'sigint');
}



class Failing_Job_Exception extends Exception
{

}

class Failing_Job
{
	public function perform()
	{
		throw new Failing_Job_Exception('Message!');
	}
}

class Test_Job_With_SetUp
{
	public $called = false;
	public $args = false;

	public function setUp()
	{
		self::$called = true;
	}

	public function perform()
	{

	}
}


class Test_Job_With_TearDown
{
	public $called = false;
	public $args = false;

	public function perform()
	{

	}

	public function tearDown()
	{
		self::$called = true;
	}
}