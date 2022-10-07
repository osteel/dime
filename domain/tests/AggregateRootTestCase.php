<?php

namespace Domain\Tests;

use EventSauce\EventSourcing\MessageDispatcher;
use EventSauce\EventSourcing\SynchronousMessageDispatcher;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase as BaseAggregateRootTestCase;

abstract class AggregateRootTestCase extends BaseAggregateRootTestCase
{
    protected function messageDispatcher(): MessageDispatcher
    {
        return new SynchronousMessageDispatcher();
    }
}
