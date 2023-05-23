<?php

declare(strict_types=1);

namespace App\Services\ObjectHydration\Hydrators;

use Attribute;
use Brick\DateTime\LocalDate;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class LocalDateHydrator implements PropertyCaster, PropertySerializer
{
    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert(is_string($value));

        return LocalDate::parse($value);
    }

    public function serialize(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert($value instanceof LocalDate);

        return (string) $value;
    }
}
