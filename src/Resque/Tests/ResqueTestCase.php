<?php

namespace Resque\Tests;

use PHPUnit_Framework_TestCase;
use Predis\Client;
use Resque\Resque;

/**
 * Resque test case class. Contains setup and teardown methods.
 *
 * @package Resque/Tests
 * @author Chris Boulton <chris@bigcommerce.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 */
abstract class ResqueTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var Resque
     */
    protected $resque;
    /**
     * @var Client
     */
    protected $redis;

    public function setUp()
    {
        parent::setUp();

        $this->redis = new Client(
            null,
            array(
                'prefix' => 'resquetest:'
            )
        );

        $this->redis->flushdb();

        $this->resque = new Resque();
        $this->resque->setRedisBackend($this->redis);
    }
}
