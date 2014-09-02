<?php

namespace Resque\Event;

class JobBeforePerformEvent implements EventInterface
{
    public function getName()
    {
        return 'resque.job.before_perform';
    }
}
