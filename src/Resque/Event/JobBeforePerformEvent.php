<?php

namespace Resque\Event;

class JobBeforePerformEvent extends AbstractJobPerformEvent implements EventInterface
{
    public function getName()
    {
        return 'resque.job.before_perform';
    }
}
