<?php

declare(strict_types=1);

namespace App\Services\ObjectHydration\Repositories;

use EventSauce\ObjectHydrator\PropertyCaster;

final class CasterRepository
{
    /** @var array<class-string,array{0:class-string<PropertyCaster>,1:array<mixed>}> */
    private array $casters = [];

    /** @param array<class-string,class-string<PropertyCaster>> $classToCasterTypeMap */
    public function __construct(array $classToCasterTypeMap)
    {
        foreach ($classToCasterTypeMap as $propertyClassName => $casterClassName) {
            $this->casters[$propertyClassName] = [$casterClassName, []];
        }
    }

    /** @return array{0:class-string<PropertyCaster>,1:array<mixed>}|null */
    public function casterFor(string $propertyClassName): ?array
    {
        return $this->casters[$propertyClassName] ?? null;
    }
}
