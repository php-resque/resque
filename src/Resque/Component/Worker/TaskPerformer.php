<?php

namespace Resque\Component\Worker;

use Resque\Component\Core\Event\EventDispatcherInterface;
use Resque\Component\Job\Event\JobFailedEvent;
use Resque\Component\Job\Event\JobInstanceEvent;
use Resque\Component\Job\Exception\InvalidJobException;
use Resque\Component\Job\Factory\JobInstanceFactoryInterface;
use Resque\Component\Job\Model\JobInterface;
use Resque\Component\Job\Model\TrackableJobInterface;
use Resque\Component\Job\PerformantJobInterface;
use Resque\Component\Job\ResqueJobEvents;
use Resque\Component\Worker\Event\WorkerJobEvent;

/**
 * Resque task performer.
 *
 * The worker handles querying issued queues for jobs, processing them and handling the result.
 */
class TaskPerformer
{
    /**
     * @var JobInstanceFactoryInterface
     */
    protected $jobInstanceFactory;
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Constructor.
     *
     * @param JobInstanceFactoryInterface $jobInstanceFactory
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        JobInstanceFactoryInterface $jobInstanceFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->jobInstanceFactory = $jobInstanceFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Execute/perform a single job.
     *
     * @throws InvalidJobException If the given job cannot actually be asked to perform.
     *
     * @param JobInterface $job The job to be processed.
     *
     * @return bool If job performed or not.
     */
    public function perform(JobInterface $job)
    {
        if ($job instanceof TrackableJobInterface) {
            $job->setState(JobInterface::STATE_PERFORMING);
        }

        try {
            $jobInstance = $this->jobInstanceFactory->createPerformantJob($job);

            if (false === ($jobInstance instanceof PerformantJobInterface)) {
                throw new InvalidJobException(
                    'Job ' . $job->getId(). ' "' . get_class($jobInstance) . '" needs to implement Resque\JobInterface'
                );
            }

            $this->eventDispatcher->dispatch(
                ResqueJobEvents::BEFORE_PERFORM,
                new JobInstanceEvent($this, $job, $jobInstance)
            );

            $jobInstance->perform($job->getArguments());

        } catch (\Exception $exception) {
            $this->handleFailedJob($job, $exception);

            return false;
        }

        if ($job instanceof TrackableJobInterface) {
            $job->setState(JobInterface::STATE_COMPLETE);
        }

        $this->eventDispatcher->dispatch(ResqueJobEvents::PERFORMED, new WorkerJobEvent($this, $job));

        return true;
    }

    /**
     * Handle failed job.
     *
     * @param JobInterface $job The job that failed.
     * @param \Exception $exception The reason the job failed.
     */
    protected function handleFailedJob(JobInterface $job, \Exception $exception)
    {
        if ($job instanceof TrackableJobInterface) {
            $job->setState(JobInterface::STATE_FAILED);
        }

        $this->eventDispatcher->dispatch(
            ResqueJobEvents::FAILED,
            new JobFailedEvent($job, $exception, $this)
        );
    }
}
