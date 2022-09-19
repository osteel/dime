<?php

namespace App;

use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;

interface Action
{
    public function handle(EventSourcedAggregateRootRepository $repository): void;
}
