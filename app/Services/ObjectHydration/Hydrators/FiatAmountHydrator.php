<?php

declare(strict_types=1);

namespace App\Services\ObjectHydration\Hydrators;

use Attribute;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class FiatAmountHydrator implements PropertyCaster, PropertySerializer
{
    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert(is_array($value));

        return new FiatAmount($value['quantity'], FiatCurrency::from($value['currency']));
    }

    public function serialize(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert($value instanceof FiatAmount);

        return ['quantity' => (string) $value->quantity, 'currency' => $value->currency->value];
    }
}
