Resque Arch
===========

The lib is a PHP port of the [Ruby Resque lib](https://github.com/resque/resque).

There are several key components that make job management work:

A Redis Server

### The Resque Service

You can get known Queues, and Workers from Redis through it, among other things. Needs a better name.

Examples:

    $resque->getAllQueues(); // Returns an array of all currently known queues in redis.
    $resque->push('asd', $job); // Sticks the job in to redis.
    $resque->work(); // Simply
    $resque->getAllWorkers(); // Returns an array of all currently known workers in redis.
### The Foreman

Holds one more Workers, when asked it will force the workers to work and keep an eye on them. Consider this non-existent for usage purposes.

### The Worker

Controls it's own run loop, asks for Jobs from Queues, and tells the Foreman about them.

### The RedisQueue

You can push and pop Jobs from it, as well interrogate the list.

Examples:

        // Enqueue a job for later processing

        $args = array(
            'int' => 123,
            'numArray' => array(
                1,
                2,
            ),
            'assocArray' => array(
                'key1' => 'value1',
                'key2' => 'value2'
            ),
        );

        $job = new Job(
            'My\Fully\Quantified\ClassName',
            $args
        );

        $queue->push($job); //
        // or
        $resque->push($queue, $job); // internally just calls $queue->push($job);
        // or
        $resque->push('foo', $job); // internally just calls, $resque->getQueue('foo'), $queue->push($job);

### The Job

Generally considered the "payload".

This is the representation of the task to do in the future. It's name is not entirely accurate for usage purposes, as the Job is technically your own classes.

### The Resque Application

Found at `bin/resque`. It provides a simple way to start some workers to process jobs.

Examples:

 * `bin/resque high,medium,low --bootstrap=MyProject/ResqueBootstrapper.php --threads=5` After including MyProject/ResqueBootstrapper.php, starts 5 workers on the high, medium and low
 * `bin/resque *` Starts one worker that will look at all queues.
 * `bin/resque * --prefix=potato` Starts one worker that will look at all queues, however when it talks to Redis all keys will be prefixed with `potato:` instead of `resque:`.
