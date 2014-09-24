<?php

namespace Resque;

/**
 * Resque job.
 *
 * @deprecated Jobs are too smart, they need to be dumb and lazy.
 *
 * @package        Resque/Job
 * @author        Chris Boulton <chris@bigcommerce.com>
 * @license        http://www.opensource.org/licenses/mit-license.php
 */
class ResqueJob
{
    /**
     * @var object Object containing details of the job.
     */
    public $payload;

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

        if ($monitor) {
            Resque_Job_Status::create($id);
        }

        return $id;
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
}
