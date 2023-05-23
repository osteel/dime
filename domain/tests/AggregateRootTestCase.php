<?php

namespace Domain\Tests;

use App\Services\ObjectHydration\ObjectMapperUsingReflectionAndClassMap;
use EventSauce\EventSourcing\Serialization\ObjectMapperPayloadSerializer;
use EventSauce\EventSourcing\Serialization\PayloadSerializer;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase as BaseAggregateRootTestCase;

abstract class AggregateRootTestCase extends BaseAggregateRootTestCase
{
    protected function payloadSerializer(): PayloadSerializer
    {
        $config = require __DIR__ . '/../../config/eventsourcing.php';

        return new ObjectMapperPayloadSerializer(new ObjectMapperUsingReflectionAndClassMap($config['hydrator_class_map']));
    }
}
