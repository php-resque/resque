<?php

namespace Resque\Component\Core\Test;

use PHPUnit_Framework_TestCase;
use Predis\Client;

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
