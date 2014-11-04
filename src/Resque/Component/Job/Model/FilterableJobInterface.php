<?php

namespace Resque\Component\Job\Model;

/**
 * Filter aware job interface
 */
interface FilterableJobInterface
{
    /**
     * Match a job to the given filter
     *
     * @param JobInterface $job The job wanting to matched against the filter.
     * @param array $filter Array of keyed conditionals. eg ['id'=>'123abc']
     *
     * @return bool true if matched, false otherwise.
     */
    public static function matchFilter(JobInterface $job, $filter = array());
}
