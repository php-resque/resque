<?php

namespace Resque\Job;

use Resque\Exception\ResqueException;

/**
 * Exception to be thrown if a job should not be performed/run.
 *
 * @deprecated Jobs that don't want to do anything, should just return. Using exceptions for behaviour is bad.
 */
class DontPerformException extends ResqueException
{
}
