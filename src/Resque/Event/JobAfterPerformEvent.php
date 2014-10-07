<?php

namespace Resque\Event;

class JobAfterPerformEvent extends AbstractJobPerformEvent implements EventInterface
{
    public function getName()
    {
        return 'resque.job.after_perform';
    }
}
