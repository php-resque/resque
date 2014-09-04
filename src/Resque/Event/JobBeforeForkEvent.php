<?php

namespace Resque\Event;

class JobBeforeForkEvent implements EventInterface
{
    public function getName()
    {
        return 'resque.job.before_fork';
    }
}
