<?php

namespace Resque;

use Resque\Exception\ResqueException;
use Resque\Job\Exception\JobNotFoundException;
use Resque\Job\PerformantJobInterface;
use Resque\Job\Status;

/**
 * Resque Job
 *
 * @todo Think of a better name for this object, as the Job is technically the class constructed by this. Payload? :s
 */
class Job
{
    /**
     * @var string Unique identifier of this job
     */
    protected $id;

    /**
     * @var array Array of arguments/parameters.
     */
    protected $arguments;

    /**
     * @var object Instance of the class performing work for this job.
     */
    protected $class;

    /**
     * @var string The current known status of this Job.
     */
    protected $status;

    /**
     * @var Queue
     *
     * @todo remove public, and make it obvious this is the queue of origin.
     */
    public $queue;

    /**
     * Constructor
     *
     * Instantiate a new instance of a job.
     *
     * @param string $jobClass The fully quantified class name of the target job to run
     * @param array $arguments An array of arguments/parameters for the job.
     */
    public function __construct($jobClass, $arguments = array())
    {
        $this->class = $jobClass;
        $this->setArguments($arguments);
    }

    /**
     * @return string
     */
    public function getId()
    {
        if (null === $this->id) {
            $this->id = md5(uniqid('', true));
        }

        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return object|string
     */
    public function getJobClass()
    {
        return $this->class;
    }

    /**
     * Get the arguments supplied to this job
     *
     * @return array Array of arguments
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Data structure for json_encode
     *
     * Based off JsonSerializable with out actually implementing it.
     *
     * @see http://php.net/manual/en/jsonserializable.jsonserialize.php for more details.
     */
    public function jsonSerialize()
    {
        return array(
            'class' => $this->getJobClass(),
            'args' => array($this->getArguments()),
            'id' => $this->getId(),
            'queue_time' => microtime(true),
        );
    }

    /**
     * Update the status of the current job.
     *
     * @param int $status Status constant from Resque_Job_Status indicating the current status of a job.
     */
    public function updateStatus($status)
    {
        if (empty($this->payload['id'])) {
            return;
        }

        $statusInstance = new Resque_Job_Status($this->payload['id']);
        $statusInstance->update($status);
    }

    /**
     * Return the status of the current job.
     *
     * @return int The status of the job as one of the Resque_Job_Status constants.
     */
    public function getStatus()
    {
        $status = new Resque_Job_Status($this->payload['id']);
        return $status->get();
    }

    /**
     * @param \Resque\Queue $queue
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

    /**
     * @param $args
     * @throws \InvalidArgumentException when $args is not an array
     */
    public function setArguments($args)
    {
        if (false === is_array($args)) {
            throw new \InvalidArgumentException(
                'Supplied $args must be an array.'
            );
        }

        $this->arguments = $args;
    }

    /**
     * Generate a string representation used to describe the current job.
     *
     * @return string The string representation of the job.
     */
    public function __toString()
    {
        return 'Job' . json_encode($this->jsonSerialize(), true);
    }

    /**
     * Clone
     *
     * On clone, remove the id. This allows recreation of a job via $queue->push(clone $job);
     */
    public function __clone()
    {
        $this->id = null;
    }
}
