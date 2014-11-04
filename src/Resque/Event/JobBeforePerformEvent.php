<?php

namespace Resque\Event;

class JobBeforePerformEvent extends AbstractJobPerformEvent
{
    public function getName()
    {
        return 'resque.job.before_perform';
    }
}
