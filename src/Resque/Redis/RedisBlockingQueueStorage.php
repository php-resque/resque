<?php

namespace Resque\Queue;

use Resque\Redis\RedisQueue;

/**
 * Resque Blocking RedisQueue
 *
 * Blocks on dequeue return immediately if queue enqueue.
 *
 * @todo this is really just to hold blpop usage until I decided how it should be a part of normal queues.
 *       it could be like wildcard, but takes a bunch of queues?
 */
class BlockingRedisQueue extends RedisQueue
{
    /**
     * Pop an item off the end of the specified queues, using blocking list dequeue,
     * decode it and return it.
     *
     * @deprecated should just dequeue() but queue might have setBlocking()?
     *
     * @param array $queues
     * @param int $timeout
     * @return null|array   Decoded item from the queue.
     */
    public function blpop(array $queues, $timeout)
    {
        $list = array();
        foreach ($queues AS $queue) {
            $list[] = 'queue:' . $queue;
        }

        $item = $this->redis->blpop($list, (int)$timeout);

        if (!$item) {
            return null;
        }

        /**
         * Normally the Resque_Redis class returns queue names without the prefix
         * But the blpop is a bit different. It returns the name as prefix:queue:name
         * So we need to strip off the prefix:queue: part
         */
        $queue = substr($item[0], strlen($this->redis->getPrefix() . 'queue:'));

        return array(
            'queue' => $queue,
            'payload' => json_decode($item[1], true)
        );
    }
}
