<?php

namespace Resque\Redis\Test;

use PHPUnit_Framework_TestCase;
use Predis\Client;
use Resque\Redis\Bridge\PredisBridge;

/**
 * Resque test case class. Contains setup and teardown methods.
 */
abstract class ResqueTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Resque\Component\Core\Resque
     */
    protected $resque;
    /**
     * @var PredisBridge
     */
    protected $redis;

    public function setUp()
    {
        parent::setUp();

        $predis = new Client(
            null,
            array(
                'prefix' => 'resquetest:'
            )
        );

        $this->redis = new PredisBridge($predis);
        $this->redis->flushdb();
    }
}
