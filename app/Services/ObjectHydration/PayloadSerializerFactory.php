<?php

declare(strict_types=1);

namespace App\Services\ObjectHydration;

use EventSauce\EventSourcing\Serialization\ObjectMapperPayloadSerializer;
use EventSauce\EventSourcing\Serialization\PayloadSerializer;
use EventSauce\ObjectHydrator\DefaultCasterRepository;
use EventSauce\ObjectHydrator\DefaultSerializerRepository;
use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;

final class PayloadSerializerFactory
{
    /** @param array<class-string,class-string<PropertyCaster&PropertySerializer>> $propertyToHydratorClassMap */
    public static function make(array $propertyToHydratorClassMap): PayloadSerializer
    {
        $casterRepository = DefaultCasterRepository::builtIn();
        $serializerRepository = DefaultSerializerRepository::builtIn();

        foreach ($propertyToHydratorClassMap as $propertyClass => $hydratorClass) {
            $casterRepository->registerDefaultCaster($propertyClass, $hydratorClass);
            $serializerRepository->registerDefaultSerializer($propertyClass, $hydratorClass);
        }

        return new ObjectMapperPayloadSerializer(new ObjectMapperUsingReflection(new DefinitionProvider(
            defaultCasterRepository: $casterRepository,
            defaultSerializerRepository: $serializerRepository,
        )));
    }
}
