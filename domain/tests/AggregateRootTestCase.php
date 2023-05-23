<?php

namespace Domain\Tests;

use EventSauce\EventSourcing\Serialization\ObjectMapperPayloadSerializer;
use EventSauce\EventSourcing\Serialization\PayloadSerializer;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase as BaseAggregateRootTestCase;

abstract class AggregateRootTestCase extends BaseAggregateRootTestCase
{
    protected function payloadSerializer(): PayloadSerializer
    {
        return new ObjectMapperPayloadSerializer();
    }
}
