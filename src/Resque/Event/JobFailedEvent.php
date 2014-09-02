<?php

namespace Resque\Event;

class JobFailedEvent implements EventInterface
{
    public function getName()
    {
        return 'resque.job.failed';
    }
}
