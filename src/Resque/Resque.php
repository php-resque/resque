<?php

namespace Resque;

use Predis\Client;

/**
 * Resque.. holds redis connection
 *
 * @deprecated no longer needed
 *
 * @package		Resque/Worker
 * @author		Chris Boulton <chris@bigcommerce.com>
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class Resque
{
    const VERSION = 'dev';

    /**
     * @var Redis\Redis Instance of Redis\Redis that talks to redis.
     */
    public static $redis = null;

    /**
     * @var mixed Host/port conbination separated by a colon, or a nested
     * array of server swith host/port pairs
     */
    protected static $redisServer = null;

    /**
     * @var int ID of Redis database to select.
     */
    protected static $redisDatabase = 0;

    /**
     * Given a host/port combination separated by a colon, set it as
     * the redis server that Resque will talk to.
     *
     * @param mixed $server Host/port combination separated by a colon, DSN-formatted URI, or
     *                      a nested array of servers with host/port pairs.
     * @param int $database
     */
    public static function setBackend($server, $database = 0)
    {
        self::$redisServer   = $server;
        self::$redisDatabase = $database;
        self::$redis         = null;
    }

    /**
     * Return an instance of the Resque_Redis class instantiated for Resque.
     *
     * @return Redis\Redis Instance of Redis\Redis.
     */
    public static function redis()
    {
        if (self::$redis !== null) {
            return self::$redis;
        }

        self::$redis = new Client(
            array(
                'prefix' => 'resque:'
            )
        );
        return self::$redis;
    }
}
