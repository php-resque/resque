<?php

namespace Resque\Event;

class JobPerformedEvent implements EventInterface
{
    public function getName()
    {
        return 'resque.job.performed';
    }
}
