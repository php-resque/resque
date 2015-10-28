PHP Resque
==========

PHP Resque is a Redis-backed library for creating background jobs, placing those jobs in queues, and processing
them some time in the future.

Build Status: [![Build Status](https://api.travis-ci.org/php-resque/resque.png?branch=master)](https://travis-ci.org/php-resque/resque)

## Background

Resque was pioneered and is developed by the fine folks at GitHub, and written in Ruby. What you're seeing here is a
port of the Resque worker and enqueue system to PHP.

For more information on Resque, visit the official GitHub project: <https://github.com/resque/resque>

This PHP port provides much the same features as the Ruby version:

* Workers can be distributed between multiple machines
* Includes support for priorities (queues)
* Resilient to memory leaks (forking)
* Expects failures
* Custom failure back ends
* Ability to dequeue jobs
* Lifecycle events

It also supports the following additional features:

* Will mark a job as failed when a forked child running a job does not exit cleanly
* Uses Redis transactions when appropriate
* Avoids singletons

## Requirements

* PHP 5.4+
* Redis 2.2+
* Composer

## Getting Started

The easiest way to work with php-resque is when it's installed as a Composer package inside your project.

If you're not familiar with Composer, please see <http://getcomposer.org/>.

Add php-resque to your application's composer.json.

```sh
composer require php-resque/resque:dev-master
```

## Jobs

### Queueing Jobs

Jobs are queued as follows:

```php
$resque = new Resque\Component\Core\Resque(/* predis connection */);
$resque->enqueue('default', 'Acme\My\Job', array('name' => 'Chris'));
```

This is assuming you're happy with the default internals.

### Defining Jobs

Each job should be in its own class, and implement the `Resque\Component\Job\PerformantJobInterface` interface.

```php
namespace Acme\My;

use Resque\Component\Job\PerformantJobInterface;

class Job implements PerformantJobInterface
{
    public function perform($args)
    {
        // Work work work
        echo $args['name'];
    }
}
```

Any exception thrown by a job will result in the job failing - be careful here and make sure you handle the
exceptions that shouldn't result in a job failing.

### Dequeueing/Removing Jobs

A queue allows you to remove jobs from it in the following ways

```php
// Simply remove it by a job id
$queue->remove(array('id' => $job->getId()));
// remove jobs by matching the class
$queue->remove(array('class' => 'Acme/Job'));
```

If no removal filters are given, no jobs are removed. However you may remove all the jobs in a queue, and the queue
itself with the following

```php
$queue->deregister();
```

Both remove and deregister return the number of deleted jobs.

### Tracking Job Statuses

php-resque has the ability to perform basic status tracking of a queued
job. The status information will allow you to check if a job is in the
queue, is currently being run, has finished, or has failed.

To track the status of a job, pass `true` as the fourth argument to
`Resque::enqueue`. A token used for tracking the job status will be
returned:

```php
$token = Resque::enqueue('default', 'My_Job', $args, true);
echo $token;
```

To fetch the status of a job:

```php
$status = new Resque_Job_Status($token);
echo $status->get(); // Outputs the status
```

Job statuses are defined as constants in the `Resque_Job_Status` class.
Valid statuses include:

* `Resque_Job_Status::STATUS_WAITING` - Job is still queued
* `Resque_Job_Status::STATUS_RUNNING` - Job is currently running
* `Resque_Job_Status::STATUS_FAILED` - Job has failed
* `Resque_Job_Status::STATUS_COMPLETE` - Job is complete
* `false` - Failed to fetch the status - is the token valid?

Statuses are available for up to 24 hours after a job has completed
or failed, and are then automatically expired. A status can also
forcefully be expired by calling the `stop()` method on a status
class.

## Workers

Workers work in much the exact same way as the Ruby workers. For complete
documentation on workers, see the original documentation.

A basic "up-and-running" `bin/resque` file is included that sets up a
running worker environment. (`vendor/bin/resque` when installed
via Composer)

The exception to the similarities with the Ruby version of resque is
how a worker is initially setup. To work under all environments,
not having a single environment such as with Ruby, the PHP port makes
*no* assumptions about your setup, outside of depending on composer.

To start a worker, it's very similar to the Ruby version:

```sh
$ QUEUE=file_serve bin/resque
```

It's your responsibility to tell the worker which file to include to get
your application underway. You do so by setting the `APP_INCLUDE` environment
variable:

```sh
$ QUEUE=file_serve APP_INCLUDE=../application/init.php bin/resque
```

Getting your application underway also includes telling the worker your job
classes, by means of either an autoloader or including them.

Alternately, you can always `include('bin/resque')` from your application and
skip setting `APP_INCLUDE` altogether.  Just be sure the various environment
variables are set (`setenv`) before you do.

### Logging

The port supports the same environment variables for logging to STDOUT.
Setting `VERBOSE` will print basic debugging information and `VVERBOSE`
will print detailed information.

```sh
$ VERBOSE=1 QUEUE=file_serve bin/resque
$ VVERBOSE=1 QUEUE=file_serve bin/resque
```

### Priorities and RedisQueue Lists

Similarly, priority and queue list functionality works exactly the same as the Ruby workers. Multiple queues
should be separated with a comma, and the order that they're supplied in is the order that they're checked in.

As per the original example:

```sh
$ QUEUE=file_serve,warm_cache bin/resque
```

The `file_serve` queue will always be checked for new jobs on each iteration before the `warm_cache` queue is checked.

### Running All Queues

All queues are supported in the same manner and processed in alphabetical order:

```sh
$ QUEUE='*' bin/resque
```

### Running Multiple Workers

Multiple workers can be launched simultaneously by supplying the `COUNT` environment variable:

```sh
$ QUEUES=emails COUNT=5 bin/resque
```

Be aware, however, that each worker is its own fork, and the original process
will shut down as soon as it has spawned `COUNT` forks.  If you need to keep
track of your workers using an external application such as `monit`, you'll
need to work around this limitation.

### Custom prefix

When you have multiple apps using the same Redis database it is better to
use a custom prefix to separate the Resque data:

```sh
$ PREFIX=my-app-name bin/resque
```

### Forking

Similarly to the Ruby versions, supported platforms will immediately
fork after picking up a job. The forked child will exit as soon as
the job finishes.

The difference with php-resque is that if a forked child does not exit cleanly (PHP error etc), php-resque
will automatically fail the job.

### Signals

Signals also work on supported platforms exactly as in the Ruby
version of Resque:

* `QUIT` - Wait for job to finish processing then exit
* `TERM` / `INT` - Immediately kill job then exit
* `USR1` - Immediately kill job but don't exit
* `USR2` - Pause worker, no new jobs will be processed
* `CONT` - Resume worker.

### Process Titles/Statuses

The Ruby version of Resque has a nifty feature whereby the process
title of the worker is updated to indicate what the worker is doing,
and any forked children also set their process title with the job
being run. This helps identify running processes on the server and
their resque status.

**PHP does not have this functionality by default until 5.5.**

A PECL module (<http://pecl.php.net/package/proctitle>) exists that
adds this functionality to PHP before 5.5, so if you'd like process
titles updated, install the PECL module as well. php-resque will
automatically detect and use it.

## Event System

php-resque comes with a basic event system that can be used by your application. However it's recommended
that you [plug in a bridge to your applications event system](#dispatcher-replacement).

In the supplied dispatcher you can listen in on events ([as listed below](#events) by registering
[callables](http://php.net/manual/en/language.types.callable.php) against them, that will be triggered when an
event is raised:

```php
// @see Resque\Component\Core\Event\EventDispatcher
$dispatcher->addListener('eventName', [callback]);
```

`[callback]` may be anything in PHP that [is callable](http://php.net/manual/en/function.is-callable.php):

Event objects are passed through as a singular argument, ([documented below](#events)).

You can stop listening to an event by calling `EventDispatcher->removeListener` with the same arguments supplied
to `EventDispatcher->addListener`.

### Events

In php-resque each event is an object with a name, and optionally other properties depending on the situation.
The following list shows each of the event names and corresponding objects that come with them. At a minimum all event
objects will implement the `Resque\Event\EventInterface` interface.

#### resque.worker.start_up

`@see Resque\Component\Worker\Event\WorkerEvent`

Dispatched once, as a worker initializes. Argument passed is the instance of the `Worker` that was just initialized.

#### resque.worker.before_fork

`@see Resque\Event\WorkerBeforeForkEvent`

Dispatched before `Resque\Component\Worker\Worker` forks to run a job. The event contains the `Worker` and the `Resque\Component\Job\Model\Job` that is
about to perform.

`resque.job.before_fork` is triggered in the **parent** process. Any changes made will be permanent for as long as
the **worker** lives.

#### resque.worker.after_fork

@see `Resque\Event\WorkerAfterForkEvent`

Dispatched from the child, after `Resque\Component\Worker\Worker` has forked to run a job (but before the job is run). The event
contains the the `Worker` and the `Resque\Component\Job\Model\Job` that is about to perform.

**Note:** `resque.job.before_fork` is triggered in the **child** process after forking out to complete a job. Any
changes made will only live as long as the **job** is being processed.

#### resque.job.before_perform

@see `Resque\Event\JobBeforePerformEvent`

Dispatched just before the `perform` method on a job is called. The event contains the instance of `Resque\Component\Job\Model\Job` that
is about to perform, and the instance of the target class on whom the `perform` method will be called.

Any exceptions thrown will be treated as if they were thrown in a job, causing the job to be marked as failed.

#### resque.job.after_perform

`@see Resque\Event\WorkerAfterForkEvent`

Dispatched immediately after a job has successfully performed. The event contains the instance of `Resque\Component\Job\Model\Job` and the
instance of the target class that just performed.

Any exceptions thrown will be treated as if they were thrown in a job, causing the job to be marked as failed.

#### resque.job.failed

`@see Resque\Component\Job\Event\JobFailedEvent`

Dispatched whenever a job fails to perform. That is when a job throws an Exception, or when a Worker's child fails
to exit cleanly.

The event contains the following:

* `Resque\Component\Job\Model\Job` The job that just failed.
* `Resque\Component\Worker\Worker` The worker that the job just failed in.
* `\Exception` The exception that was thrown when the job failed, if one was thrown to cause it to fail.

#### afterEnqueue

Called after a job has been queued using the `Resque::enqueue` method. Arguments passed
(in this order) include:

* Class - string containing the name of scheduled job
* Arguments - array of arguments supplied to the job
* RedisQueue - string containing the name of the queue the job was added to
* ID - string containing the new token of the enqueued job

### Dispatcher Replacement

// @todo document usage
