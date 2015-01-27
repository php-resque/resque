<?php

namespace Resque\Component\Job\Model;

/**
 * Job Interface
 */
interface JobInterface
{
    const STATE_WAITING    = 'waiting';
    const STATE_PERFORMING = 'performing';
    const STATE_FAILED     = 'failed';
    const STATE_COMPLETE   = 'complete';

    /**
     * Set Id
     *
     * @param string $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get id
     *
     * @return string
     */
    public function getId();

    /**
     * Set target job class
     *
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

    /**
     * Set arguments
     *
     * @param array $args An array of parameters for the job.
     * @throws \InvalidArgumentException when $args is not an array
     */
    public function setArguments($args);

    /**
     * Get the arguments supplied to this job
     *
     * @return array Array of arguments
     */
    public function getArguments();

    /**
     * Encode
     *
     * Given an instance of JobInterface encode it in such a way that the result once passed to self::decode() will
     * result in an identical copy of JobInterface.
     *
     * @return mixed Encoded data to be saved into redis and at a later date passed to self->decode()
     */
    public function encode();

    /**
     * Decode
     *
     * Decodes data from self::encode() and returns a new instance of JobInterface that the payload was
     * created from.
     *
     * @param mixed $payload The encoded data from self->encode()
     * @return static
     */
    public static function decode($payload);
}
