<?php

declare(strict_types=1);

namespace App\Services\ObjectHydration\Hydrators;

use Attribute;
use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityAllocation;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\ObjectHydrator\ObjectMapper;
use EventSauce\ObjectHydrator\PropertyCaster;
use EventSauce\ObjectHydrator\PropertySerializer;
use ReflectionClass;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class SharePoolingAssetDisposalHydrator implements PropertyCaster, PropertySerializer
{
    public function cast(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert(is_array($value));

        return new SharePoolingAssetDisposal(
            id: ! empty($value['id']) ? SharePoolingAssetTransactionId::fromString($value['id']) : null,
            date: LocalDate::parse($value['date']),
            quantity: new Quantity($value['quantity']),
            costBasis: new FiatAmount($value['cost_basis']['quantity'], FiatCurrency::from($value['cost_basis']['currency'])),
            proceeds: new FiatAmount($value['proceeds']['quantity'], FiatCurrency::from($value['proceeds']['currency'])),
            forFiat: (bool) $value['for_fiat'],
            sameDayQuantityAllocation: new QuantityAllocation(
                array_map(fn (string $quantity) => new Quantity($quantity), $value['same_day_quantity_allocation']),
            ),
            thirtyDayQuantityAllocation: new QuantityAllocation(
                array_map(fn (string $quantity) => new Quantity($quantity), $value['thirty_day_quantity_allocation']),
            ),
            processed: (bool) $value['processed'],
        );
    }

    public function serialize(mixed $value, ObjectMapper $hydrator): mixed
    {
        assert($value instanceof SharePoolingAssetDisposal);

        return [
            'id' => (string) $value->id ?: null,
            'date' => (string) $value->date,
            'quantity' => (string) $value->quantity,
            'cost_basis' => ['quantity' => (string) $value->costBasis->quantity, 'currency' => $value->costBasis->currency->value],
            'proceeds' => ['quantity' => (string) $value->proceeds->quantity, 'currency' => $value->proceeds->currency->value],
            'for_fiat' => $value->forFiat,
            'same_day_quantity_allocation' => $this->serializeQuantityAllocation($value->sameDayQuantityAllocation),
            'thirty_day_quantity_allocation' => $this->serializeQuantityAllocation($value->thirtyDayQuantityAllocation),
            'processed' => $value->processed,
        ];
    }

    /** @return array<string,string> */
    private function serializeQuantityAllocation(QuantityAllocation $quantityAllocation): array
    {
        $reflectionClass = new ReflectionClass($quantityAllocation);

        return array_map(
            fn (Quantity $quantity) => (string) $quantity, // @phpstan-ignore-line
            $reflectionClass->getProperty('allocation')->getValue($quantityAllocation), // @phpstan-ignore-line
        );
    }
}
