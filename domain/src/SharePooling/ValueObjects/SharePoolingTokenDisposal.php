<?php

namespace Domain\SharePooling\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Tests\SharePooling\Factories\ValueObjects\SharePoolingTokenDisposalFactory;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

final class SharePoolingTokenDisposal extends SharePoolingTransaction
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly Quantity $quantity,
        public readonly FiatAmount $costBasis,
        public readonly FiatAmount $proceeds,
        public readonly QuantityBreakdown $sameDayQuantityBreakdown,
        public readonly QuantityBreakdown $thirtyDayQuantityBreakdown,
        protected bool $processed = true,
    ) {
    }

    protected static function newFactory(): SharePoolingTokenDisposalFactory
    {
        return SharePoolingTokenDisposalFactory::new();
    }

    public function copy(): static
    {
        return (new self(
            $this->date,
            $this->quantity,
            $this->costBasis,
            $this->proceeds,
            $this->sameDayQuantityBreakdown->copy(),
            $this->thirtyDayQuantityBreakdown->copy(),
            $this->processed,
        ))->setPosition($this->position);
    }

    /** Return a copy of the disposal with reset quantities and marked as unprocessed. */
    public function copyAsUnprocessed(): SharePoolingTokenDisposal
    {
        return (new SharePoolingTokenDisposal(
            date: $this->date,
            quantity: $this->quantity,
            costBasis: $this->costBasis->nilAmount(),
            proceeds: $this->proceeds,
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
            processed: false,
        ))->setPosition($this->position);
    }

    public function sameDayQuantity(): Quantity
    {
        return $this->sameDayQuantityBreakdown->quantity();
    }

    public function thirtyDayQuantity(): Quantity
    {
        return $this->thirtyDayQuantityBreakdown->quantity();
    }

    /** @throws \Domain\SharePooling\ValueObjects\Exceptions\QuantityBreakdownException */
    public function hasThirtyDayQuantityMatchedWith(SharePoolingTokenAcquisition $acquisition): bool
    {
        return $this->thirtyDayQuantityBreakdown->hasQuantityMatchedWith($acquisition);
    }

    /** @throws \Domain\SharePooling\ValueObjects\Exceptions\QuantityBreakdownException */
    public function thirtyDayQuantityMatchedWith(SharePoolingTokenAcquisition $acquisition): Quantity
    {
        return $this->thirtyDayQuantityBreakdown->quantityMatchedWith($acquisition);
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
