<?php

namespace Resque\Event;

class JobAfterPerformEvent extends AbstractJobPerformEvent
{
    public function getName()
    {
        return 'resque.job.after_perform';
    }
}
