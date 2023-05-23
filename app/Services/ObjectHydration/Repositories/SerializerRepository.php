<?php

declare(strict_types=1);

namespace App\Services\ObjectHydration\Repositories;

use EventSauce\ObjectHydrator\PropertySerializer;

class SerializerRepository
{
    /** @var array<class-string,array{0:class-string<PropertySerializer>,1:array<mixed>}> */
    private array $serializers = [];

    /** @param array<class-string,class-string<PropertySerializer>> $classToSerializerTypeMap */
    public function __construct(array $classToSerializerTypeMap)
    {
        foreach ($classToSerializerTypeMap as $propertyClassName => $casterClassName) {
            $this->serializers[$propertyClassName] = [$casterClassName, []];
        }
    }

    /** @return array{0:class-string<PropertySerializer>,1:array<mixed>}|null */
    public function serializerForType(string $type): ?array
    {
        return $this->serializers[$type] ?? null;
    }

    /** @return array<string,array{0:class-string<PropertySerializer>,1:array<mixed>}> */
    public function allSerializersPerType(): array
    {
        return $this->serializers;
    }
}
