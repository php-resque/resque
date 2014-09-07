<?php

namespace Resque;

/**
 * Resque job.
 *
 * @deprecated Jobs are too smart, they need to be dumb and lazy.
 *
 * @package		Resque/Job
 * @author		Chris Boulton <chris@bigcommerce.com>
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class ResqueJob
{
	/**
	 * @var Queue The name of the queue that this job belongs to.
	 */
	public $queue;

	/**
	 * @var Worker Instance of the Resque worker running this job.
	 */
	public $worker;

	/**
	 * @var object Object containing details of the job.
	 */
	public $payload;

	/**
	 * Instantiate a new instance of a job.
	 *
	 * @param Queue $queue The queue that the job belongs to.
	 * @param array $payload array containing details of the job.
	 */
	public function __construct(Queue $queue, $payload)
	{
		$this->queue = $queue;
		$this->payload = $payload;
	}

	/**
	 * Create a new job and save it to the specified queue.
	 *
	 * @param string $queue The name of the queue to place the job in.
	 * @param string $class The name of the class that contains the code to execute the job.
	 * @param array $args Any optional arguments that should be passed when the job is executed.
	 * @param boolean $monitor Set to true to be able to monitor the status of a job.
	 *
	 * @return string
	 */
	public function create($queue, $class, $args = null, $monitor = false)
	{
		$id = md5(uniqid('', true));
		Resque::push($queue, array(
			'class'	=> $class,
			'args'	=> array($args),
			'id'	=> $id,
			'queue_time' => microtime(true),
		));

		if($monitor) {
			Resque_Job_Status::create($id);
		}

		return $id;
	}

    /**
     * Find the next available job from the specified queues using blocking list pop
     * and return an instance of Job for it.
     *
     * @param array             $queues
     * @param int               $timeout
     * @return null|object Null when there aren't any waiting jobs, instance of Job when a job was found.
     */
    public function reserveBlocking(array $queues, $timeout = null)
    {
        $item = Resque::blpop($queues, $timeout);

        if(!is_array($item)) {
            return false;
        }

        return new Job($item['queue'], $item['payload']);
    }

	/**
	 * Update the status of the current job.
	 *
	 * @param int $status Status constant from Resque_Job_Status indicating the current status of a job.
	 */
	public function updateStatus($status)
	{
		if(empty($this->payload['id'])) {
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
	 * Re-queue the current job.
	 * @return string
	 */
	public function recreate()
	{
		$status = new Resque_Job_Status($this->payload['id']);
		$monitor = false;
		if($status->isTracking()) {
			$monitor = true;
		}

		return self::create($this->queue, $this->payload['class'], $this->getArguments(), $monitor);
	}
}
