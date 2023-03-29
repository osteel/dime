<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Tests\Aggregates\SharePoolingAsset\Factories\ValueObjects\SharePoolingAssetDisposalFactory;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class SharePoolingAssetDisposal extends SharePoolingAssetTransaction implements SerializablePayload
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly Quantity $quantity,
        public readonly FiatAmount $costBasis,
        public readonly FiatAmount $proceeds,
        public readonly QuantityAllocation $sameDayQuantityAllocation,
        public readonly QuantityAllocation $thirtyDayQuantityAllocation,
        protected bool $processed = true,
    ) {
    }

    /** @return SharePoolingAssetDisposalFactory<static> */
    protected static function newFactory(): SharePoolingAssetDisposalFactory
    {
        return SharePoolingAssetDisposalFactory::new();
    }

    public function copy(): static
    {
        return (new self(
            $this->date,
            $this->quantity,
            $this->costBasis,
            $this->proceeds,
            $this->sameDayQuantityAllocation->copy(),
            $this->thirtyDayQuantityAllocation->copy(),
            $this->processed,
        ))->setPosition($this->position);
    }

    /** Return a copy of the disposal with reset quantities and marked as unprocessed. */
    public function copyAsUnprocessed(): SharePoolingAssetDisposal
    {
        return (new SharePoolingAssetDisposal(
            date: $this->date,
            quantity: $this->quantity,
            costBasis: $this->costBasis->zero(),
            proceeds: $this->proceeds,
            sameDayQuantityAllocation: new QuantityAllocation(),
            thirtyDayQuantityAllocation: new QuantityAllocation(),
            processed: false,
        ))->setPosition($this->position);
    }

    public function sameDayQuantity(): Quantity
    {
        return $this->sameDayQuantityAllocation->quantity();
    }

    public function thirtyDayQuantity(): Quantity
    {
        return $this->thirtyDayQuantityAllocation->quantity();
    }

    /** @throws \Domain\Aggregates\SharePoolingAsset\ValueObjects\Exceptions\QuantityAllocationException */
    public function hasThirtyDayQuantityMatchedWith(SharePoolingAssetAcquisition $acquisition): bool
    {
        return $this->thirtyDayQuantityAllocation->hasQuantityAllocatedTo($acquisition);
    }

    /** @throws \Domain\Aggregates\SharePoolingAsset\ValueObjects\Exceptions\QuantityAllocationException */
    public function thirtyDayQuantityMatchedWith(SharePoolingAssetAcquisition $acquisition): Quantity
    {
        return $this->thirtyDayQuantityAllocation->quantityAllocatedTo($acquisition);
    }

    /** @return array{date:string,quantity:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},same_day_quantity_allocation:array{allocation:array<int,string>},thirty_day_quantity_allocation:array{allocation:array<int,string>},processed:bool,position:int|null} */
    public function toPayload(): array
    {
        return [
            'date' => $this->date->__toString(),
            'quantity' => $this->quantity->__toString(),
            'cost_basis' => $this->costBasis->toPayload(),
            'proceeds' => $this->proceeds->toPayload(),
            'same_day_quantity_allocation' => $this->sameDayQuantityAllocation->toPayload(),
            'thirty_day_quantity_allocation' => $this->thirtyDayQuantityAllocation->toPayload(),
            'processed' => $this->processed,
            'position' => $this->position,
        ];
    }

    /** @param array{date:string,quantity:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},same_day_quantity_allocation:array{allocation:array<int,string>},thirty_day_quantity_allocation:array{allocation:array<int,string>},processed:bool,position:int} $payload */
    public static function fromPayload(array $payload): static
    {
        return (new static(
            LocalDate::parse($payload['date']),
            new Quantity($payload['quantity']),
            FiatAmount::fromPayload($payload['cost_basis']),
            FiatAmount::fromPayload($payload['proceeds']),
            QuantityAllocation::fromPayload($payload['same_day_quantity_allocation']),
            QuantityAllocation::fromPayload($payload['thirty_day_quantity_allocation']),
            (bool) $payload['processed'],
        ))->setPosition((int) $payload['position']);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s: disposed of %s tokens for %s (cost basis: %s)',
            $this->date,
            $this->quantity,
            $this->proceeds,
            $this->costBasis,
        );
    }
}
