<?php

declare(strict_types=1);

namespace App\Services\ObjectHydrators;

use Attribute;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class CapitalGainHydrator implements PropertyCaster, PropertySerializer
{
    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert(is_array($value));

        return new CapitalGain(
            costBasis: new FiatAmount($value['cost_basis']['quantity'], FiatCurrency::from($value['cost_basis']['currency'])),
            proceeds: new FiatAmount($value['proceeds']['quantity'], FiatCurrency::from($value['proceeds']['currency'])),
        );
    }

    public function serialize(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert($value instanceof CapitalGain);

        return [
            'cost_basis' => ['quantity' => (string) $value->costBasis->quantity, 'currency' => $value->costBasis->currency->value],
            'proceeds' => ['quantity' => (string) $value->proceeds->quantity, 'currency' => $value->proceeds->currency->value],
            'difference' => ['quantity' => (string) $value->difference->quantity, 'currency' => $value->difference->currency->value],
        ];
    }
}
