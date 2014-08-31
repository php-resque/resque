<?php

namespace Resque\Redis;

interface RedisInterface
{
    public function disconnect();

    public function set($key, $value);
    public function get($key);
    public function del($key);

    public function sadd($key, $value);
    public function srem($key, $value);

    public function rpush($key, $value);
}
