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
     * Set Id.
     *
     * @param string $id The id of this job.
     *
     * @return $this
     */
    public function setId($id);

    /**
     * Get Id.
     *
     * @return string
     */
    public function getId();

    /**
     * Set target job class
     *
     * @todo I think I should rename this to "target". It's up to the instance factory to know what it means..
     *
     * @param $class
     *
     * @return void
     */
    public function setJobClass($class);

    /**
     * Get target job class.
     *
     * @return string
     */
    public function getJobClass();

    /**
     * Set arguments.
     *
     * @param array $args An array of parameters for the job.
     * @throws \InvalidArgumentException when $args is not an array.
     *
     * @return void
     */
    public function setArguments($args);

    /**
     * Get the arguments.
     *
     * @return array Array of arguments
     */
    public function getArguments();

    // @todo Establish if this is to be optional (via OriginQueueAwareInterface) or always on a JobInterface.
    public function getOriginQueue();

    /**
     * Encode.
     *
     * Given an instance of JobInterface encode it in such a way that the result once passed to self::decode() will
     * result in an identical copy of JobInterface.
     *
     * @deprecated The job object should not care about how it is "encoded" in storage.
     *
     * @return mixed Encoded data to be saved into redis and at a later date passed to self->decode()
     */
    public function encode();

    /**
     * Decode.
     *
     * Decodes data from self::encode() and returns a new instance of JobInterface that the payload was
     * created from.
     *
     * @deprecated The job object should not care about how it is "encoded" in storage.
     * @todo move the responsibility of decoding/encoding to the storage layer.
     *
     * @param mixed $payload The encoded data from self->encode()
     * @return static
     */
    public static function decode($payload);
}
