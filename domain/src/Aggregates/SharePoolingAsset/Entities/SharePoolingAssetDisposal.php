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

final class SharePoolingAssetDisposal extends SharePoolingAssetTransaction
{
    public function __construct(
        LocalDate $date,
        Quantity $quantity,
        FiatAmount $costBasis,
        public readonly FiatAmount $proceeds,
        public readonly bool $forFiat,
        public readonly QuantityAllocation $sameDayQuantityAllocation = new QuantityAllocation(),
        public readonly QuantityAllocation $thirtyDayQuantityAllocation = new QuantityAllocation(),
        bool $processed = true,
        ?SharePoolingAssetTransactionId $id = null,
    ) {
        parent::__construct($date, $quantity, $costBasis, id: $id, processed: $processed);

        $costBasis->currency === $proceeds->currency
            || throw SharePoolingAssetDisposalException::currencyMismatch($costBasis->currency, $proceeds->currency);

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
            forFiat: $this->forFiat,
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

    public function __toString(): string
    {
        return sprintf(
            '%s: disposed of %s tokens for %s (cost basis: %s, for fiat: %s)',
            $this->date,
            $this->quantity,
            $this->proceeds,
            $this->costBasis,
            $this->forFiat ? 'yes' : 'no',
        );
    }
}
