<?php

namespace Domain\Tests\Aggregates;

use App\Services\ObjectHydration\PayloadSerializerFactory;
use EventSauce\EventSourcing\Serialization\PayloadSerializer;
use EventSauce\EventSourcing\TestUtilities\AggregateRootTestCase as BaseAggregateRootTestCase;

abstract class AggregateRootTestCase extends BaseAggregateRootTestCase
{
    protected function payloadSerializer(): PayloadSerializer
    {
        $config = require __DIR__ . '/../../../config/eventsourcing.php';

        return PayloadSerializerFactory::make($config['hydrator_class_map']);
    }
}
