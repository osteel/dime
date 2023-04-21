<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Entities;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Entities\Exceptions\SharePoolingAssetDisposalException;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\QuantityAllocation;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\Tests\Aggregates\SharePoolingAsset\Factories\Entities\SharePoolingAssetDisposalFactory;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class SharePoolingAssetDisposal extends SharePoolingAssetTransaction implements SerializablePayload
{
    public readonly QuantityAllocation $sameDayQuantityAllocation;
    public readonly QuantityAllocation $thirtyDayQuantityAllocation;

    public function __construct(
        LocalDate $date,
        Quantity $quantity,
        FiatAmount $costBasis,
        public readonly FiatAmount $proceeds,
        QuantityAllocation $sameDayQuantityAllocation = null,
        QuantityAllocation $thirtyDayQuantityAllocation = null,
        bool $processed = true,
        ?SharePoolingAssetTransactionId $id = null,
    ) {
        parent::__construct($date, $quantity, $costBasis, id: $id, processed: $processed);

        $costBasis->currency === $proceeds->currency
            || throw SharePoolingAssetDisposalException::currencyMismatch($costBasis->currency, $proceeds->currency);

        $this->sameDayQuantityAllocation = $sameDayQuantityAllocation ?? new QuantityAllocation();
        $this->thirtyDayQuantityAllocation = $thirtyDayQuantityAllocation ?? new QuantityAllocation();

        $allocatedQuantity = $this->sameDayQuantityAllocation->quantity()->plus($this->thirtyDayQuantityAllocation->quantity());

        $this->quantity->isGreaterThanOrEqualTo($allocatedQuantity)
            || throw SharePoolingAssetDisposalException::excessiveQuantityAllocated($this->quantity, $allocatedQuantity);
    }

    /** @return SharePoolingAssetDisposalFactory<static> */
    protected static function newFactory(): SharePoolingAssetDisposalFactory
    {
        return SharePoolingAssetDisposalFactory::new();
    }

    /** Return a copy of the disposal with a reset cost basis and marked as unprocessed. */
    public function copyAsUnprocessed(): SharePoolingAssetDisposal
    {
        return new self(
            id: $this->id,
            date: $this->date,
            quantity: $this->quantity,
            costBasis: $this->costBasis->zero(),
            proceeds: $this->proceeds,
            processed: false,
        );
    }

    public function sameDayQuantity(): Quantity
    {
        return $this->sameDayQuantityAllocation->quantity();
    }

    public function thirtyDayQuantity(): Quantity
    {
        return $this->thirtyDayQuantityAllocation->quantity();
    }

    public function hasThirtyDayQuantityAllocatedTo(SharePoolingAssetAcquisition $acquisition): bool
    {
        return $this->thirtyDayQuantityAllocation->hasQuantityAllocatedTo($acquisition);
    }

    public function thirtyDayQuantityAllocatedTo(SharePoolingAssetAcquisition $acquisition): Quantity
    {
        return $this->thirtyDayQuantityAllocation->quantityAllocatedTo($acquisition);
    }

    /** @return array{id:string,date:string,quantity:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},same_day_quantity_allocation:array{allocation:array<string,string>},thirty_day_quantity_allocation:array{allocation:array<string,string>},processed:bool} */
    public function toPayload(): array
    {
        return [
            'id' => (string) $this->id,
            'date' => (string) $this->date,
            'quantity' => (string) $this->quantity,
            'cost_basis' => $this->costBasis->toPayload(),
            'proceeds' => $this->proceeds->toPayload(),
            'same_day_quantity_allocation' => $this->sameDayQuantityAllocation->toPayload(),
            'thirty_day_quantity_allocation' => $this->thirtyDayQuantityAllocation->toPayload(),
            'processed' => $this->processed,
        ];
    }

    /** @param array{id:string,date:string,quantity:string,cost_basis:array{quantity:string,currency:string},proceeds:array{quantity:string,currency:string},same_day_quantity_allocation:array{allocation:array<string,string>},thirty_day_quantity_allocation:array{allocation:array<string,string>},processed:bool} $payload */
    public static function fromPayload(array $payload): static
    {
        return new static(
            id: SharePoolingAssetTransactionId::fromString($payload['id']),
            date: LocalDate::parse($payload['date']),
            quantity: new Quantity($payload['quantity']),
            costBasis: FiatAmount::fromPayload($payload['cost_basis']),
            proceeds: FiatAmount::fromPayload($payload['proceeds']),
            sameDayQuantityAllocation: QuantityAllocation::fromPayload($payload['same_day_quantity_allocation']),
            thirtyDayQuantityAllocation: QuantityAllocation::fromPayload($payload['thirty_day_quantity_allocation']),
            processed: (bool) $payload['processed'],
        );
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
