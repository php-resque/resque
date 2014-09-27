<?php

namespace Resque\Job;

/**
 * Job Interface
 */
interface JobInterface
{
    /**
     * Get target job class
     *
     * @return string
     */
    public function getJobClass();

    /**
     * Encode
     *
     * Given an instance of JobInterface encode it in such a way that the result once passed to self::decode() will
     * result in an identical copy of JobInterface.
     *
     * @param JobInterface $job
     * @return mixed Encoded data to be saved into redis and at a later date passed to self::decode()
     */
    public static function encode(JobInterface $job);

    /**
     * Decode
     *
     * Decodes data from self::encode() and returns a new instance of JobInterface that the payload was
     * created from.
     *
     * @param mixed $payload The encoded data from self::encode()
     * @return JobInterface
     */
    public static function decode($payload);
}
