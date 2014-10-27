<?php

namespace Resque\Tests;

use PHPUnit_Framework_TestCase;
use Predis\Client;
use Resque\Resque;

/**
 * Resque test case class. Contains setup and teardown methods.
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
    }
}
