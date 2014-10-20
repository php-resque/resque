<?php

namespace Resque;

use Resque\Job\FilterAwareJobInterface;
use Resque\Job\JobInterface;
use Resque\Job\QueueAwareJobInterface;
use Resque\Queue\QueueInterface;

/**
 * Resque Job
 *
 * @todo Think of a better name for this object, as the Job is technically the class constructed by this. Payload? :s
 */
class Job implements
    JobInterface,
    QueueAwareJobInterface,
    FilterAwareJobInterface
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
     * @var QueueInterface|null The queue this job was popped from, if it was popped.
     */
    protected $originQueue;

    /**
     * Constructor
     *
     * Instantiate a new instance of a job.
     *
     * @param string $jobClass The fully quantified class name of the target job to run
     * @param array $arguments An array of arguments/parameters for the job.
     */
    public function __construct($jobClass = null, $arguments = array())
    {
        $this->class = $jobClass;
        $this->setArguments($arguments);
//        if ($monitor) {
//            Resque_Job_Status::create($id);
//        }
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * @param string $class
     * @return $this
     */
    public function setJobClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return object|string
     */
    public function getJobClass()
    {
        return $this->class;
    }

    /**
     * setArguments
     *
     * @param array $args An array of parameters for the job.
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
     * Get the arguments supplied to this job
     *
     * @return array Array of arguments
     */
    public function getArguments()
    {
        return $this->arguments;
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
     * {@inheritDoc}
     */
    public function setOriginQueue(QueueInterface $queue)
    {
        $this->originQueue = $queue;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOriginQueue()
    {
        return $this->originQueue;
    }

    /*
     * Data structure for self::encode
     *
     * Based off JsonSerializable with out actually implementing it.
     * @see http://php.net/manual/en/jsonserializable.jsonserialize.php for more details.
     */
    public function jsonSerialize()
    {
        return array(
            'class' => $this->getJobClass(),
            'args' => array($this->getArguments()),
            'id' => $this->getId(),
            'queue_time' => microtime(true), // @todo this isn't queue time. $queue->push() is queue time.
        );
    }

    /**
     * Generate a string representation used to describe the current job.
     *
     * @return string The string representation of the job.
     */
    public function __toString()
    {
        return 'Job' . $this::encode($this);
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

    /**
     * {@inheritdoc}
     */
    public static function matchFilter(JobInterface $job, $filter = array())
    {
        $filters = array(
            'id' => function (JobInterface $job, $filter) {
                if (isset($filter['id'])) {

                    return $filter['id'] === $job->getId();
                }

                return null;
            },
            'class' => function (JobInterface $job, $filter) {
                if (isset($filter['class'])) {

                    return $filter['class'] === $job->getJobClass();
                }

                return null;
            },
        );

        $results = array();

        foreach ($filters as $filterName => $filterCallback) {
            $result = $filterCallback($job, $filter);
            // Discard null results as that is the callback telling us it's conditional is not set.
            if (null === $result) {

                continue;
            }

            $results[$filterName] = $result;
        }

        return (count(array_unique($results)) === 1) && reset($results) === true;
    }

    /**
     * Create Redis payload
     *
     * @param JobInterface $job
     * @return string JSON object to store in redis.
     */
    public static function encode(JobInterface $job)
    {
        return json_encode(
            $job->jsonSerialize()
        );
    }

    public static function decode($payload)
    {
        $payload = json_decode($payload, true);

        // @todo check for json_decode error, if error throw an exception.

        $job = new static();
        $job->setJobClass($payload['class']);
        $job->setArguments($payload['args'][0]);
        $job->setId($payload['id']);

        return $job;
    }
}
