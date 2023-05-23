<?php

declare(strict_types=1);

namespace App\Services\ObjectHydrators;

use Attribute;
use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class SharePoolingAssetAcquisitionHydrator implements PropertyCaster, PropertySerializer
{
    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert(is_array($value));

        return new SharePoolingAssetAcquisition(
            id: ! empty($value['id']) ? SharePoolingAssetTransactionId::fromString($value['id']) : null,
            date: LocalDate::parse($value['date']),
            quantity: new Quantity($value['quantity']),
            costBasis: new FiatAmount($value['cost_basis']['quantity'], FiatCurrency::from($value['cost_basis']['currency'])),
            sameDayQuantity: new Quantity($value['same_day_quantity']),
            thirtyDayQuantity: new Quantity($value['thirty_day_quantity']),
        );
    }

    public function serialize(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert($value instanceof SharePoolingAssetAcquisition);

        return [
            'id' => (string) $value->id ?: null,
            'date' => (string) $value->date,
            'quantity' => (string) $value->quantity,
            'cost_basis' => ['quantity' => (string) $value->costBasis->quantity, 'currency' => $value->costBasis->currency->value],
            'same_day_quantity' => (string) $value->sameDayQuantity(),
            'thirty_day_quantity' => (string) $value->thirtyDayQuantity(),
        ];
    }
}
