<?php

namespace Resque;

use Resque\Exception\ResqueException;
use Resque\Job\Exception\JobNotFoundException;
use Resque\Job\JobInterface;
use Resque\Job\Status;

/**
 * Resque job
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
     * @param string $jobClass The fully quantified class name of the target job to run.
     * @param array $arguments An array of arguments/parameters for the job.
     */
    public function __construct($jobClass, $arguments = array())
    {
        $this->id = md5(uniqid('', true));
        $this->class = $jobClass;
        $this->arguments = $arguments;
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
            'class' => $this->class,
            'args' => array($this->arguments),
            'id' => $this->id,
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
     * Actually execute a job by calling the perform method on the class
     * associated with the job with the supplied arguments.
     *
     * @return bool
     * @throws JobNotFoundException When the job's class could not be found or it does not contain a perform method.
     */
    public function perform()
    {
        try {
            //Resque_Event::trigger('beforePerform', $this); @todo The worker should trigger this.

            $instance = $this->createInstance();

            if (method_exists($instance, 'setUp')) {
                $instance->setUp();
            }

            $instance->perform();

            if (method_exists($instance, 'tearDown')) {
                $instance->tearDown();
            }

            //Resque_Event::trigger('afterPerform', $this);  @todo The worker should trigger this.
        } // beforePerform/setUp have said don't perform this job. Return.
        catch (Resque_Job_DontPerform $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the instantiated object for this job that will be performing work.
     *
     * @return object An instance of the target class that this job is for.
     * @throws Resque_Exception
     */
    protected function createInstance()
    {
//        if (!is_null($this->instance)) {
//            return $this->instance;
//        }

        if (false === class_exists($this->class)) {
            throw new JobNotFoundException(
                'Could not find job class ' . $this->class . '.'
            );
        }

        $instance = new $this->class;
        // @todo check if JobInterface is implemented.

        if (false === $instance instanceof JobInterface) {
            throw new ResqueException(
                'Job class ' . $this->class . ' needs to implement Resque\JobInterface'
            );
        }

        $instance->job = $this;
//        $this->instance->args = $this->getArguments();
//        $this->instance->queue = $this->queue;

        return $instance;
    }

    /**
     * @param \Resque\Queue $queue
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

    /**
     * Mark the current job as having failed.
     *
     * @param $exception
     */
    public function fail($exception)
    {
//        Event::trigger('onFailure', array(
//                'exception' => $exception,
//                'job' => $this,
//            ));
//
//        $this->updateStatus(Status::STATUS_FAILED);
//        Failure::create(
//            $this->payload,
//            $exception,
//            $this->worker,
//            $this->queue
//        );
        Stat::incr('failed');
        Stat::incr('failed:' . $this->worker);
    }

    /**
     * Generate a string representation used to describe the current job.
     *
     * @return string The string representation of the job.
     */
    public function __toString()
    {
        $name = array(
            'Job{' . $this->queue . '}'
        );
        if (!empty($this->id)) {
            $name[] = 'ID: ' . $this->id;
        }
        $name[] = $this->getJobClass();
        if (!empty($this->getArguments())) {
            $name[] = json_encode($this->getArguments());
        }
        return '(' . implode(' | ', $name) . ')';
    }
}
