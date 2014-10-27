<?php

namespace Resque\Tests\Queue;

use Resque\Job;
use Resque\Queue;
use Resque\Queue\BlockingQueue;
use Resque\Tests\ResqueTestCase;

class BlockingQueueTest extends ResqueTestCase
{
    public function testBlockingListPop()
    {
        return self::markTestSkipped();

        $worker = new Worker('jobs');
        $worker->setLogger(new Resque_Log());
        $worker->registerWorker();

        Resque::enqueue('jobs', 'Test_Job_1');
        Resque::enqueue('jobs', 'Test_Job_2');

        $i = 1;
        while ($job = $worker->reserve(true, 1)) {
            $this->assertEquals('Test_Job_' . $i, $job->payload['class']);

            if ($i == 2) {
                break;
            }

            $i++;
        }

        $this->assertEquals(2, $i);
    }
}
