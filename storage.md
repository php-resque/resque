Storage
========

Attempting to document high level Redis interactions.

## Worker

### all

smembers on `workers`

### register

Creates worker in redis.

sadd [workerId] to `workers`
set `worker:[workerId]:started`

### deregister

Removes worker from redis.

del `worker:[workerId]`
del `worker:[workerId]:started`
del processed stars `Stat::clear('processed:' . $id);`
del failed stats `Stat::clear('failed:' . $id);`
srem [workerId] from `workers`

## Queue

### all

smembers on `queues`

### register

Registers the queue.

sadd [queueName] into `queues`;

### deregister

Removes the list, and all jobs.

srem [queueName] from `queues`
del `queue:[queueName]`

### push

Puts a job payload on the end of a list

rpush into `queue:[queueName]` json encoded object.

### pop

Pulls a job payload from the end of a list

lpop from `queue:[queueName]`

### size

The number of items left in the list

llen `queue:[queueName]`
