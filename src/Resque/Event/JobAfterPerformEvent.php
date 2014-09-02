<?php

namespace Resque\Event;

class JobAfterPerformEvent implements EventInterface
{
    public function getName()
    {
        return 'resque.job.after_perform';
    }
}
