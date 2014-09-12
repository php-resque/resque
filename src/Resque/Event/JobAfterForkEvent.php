<?php

namespace Resque\Event;

class JobAfterForkEvent implements EventInterface
{
    public function getName()
    {
        return 'resque.job.after_fork';
    }
}
