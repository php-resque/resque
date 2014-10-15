<?php

namespace Resque\Job;

/**
 * Job Interface
 */
interface JobInterface
{
    /**
     * @param string $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getId();

    /**
     * @param $class
     * @return $this
     */
    public function setJobClass($class);

    /**
     * Get target job class
     *
     * @return string
     */
    public function getJobClass();

    public function setArguments($args);
    public function getArguments();

    /**
     * Data structure for self::encode, or other logging purposes
     *
     * Based off JsonSerializable with out actually implementing it.
     * @see http://php.net/manual/en/jsonserializable.jsonserialize.php for more details.
     *
     * @return mixed
     */
    public function jsonSerialize();

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
