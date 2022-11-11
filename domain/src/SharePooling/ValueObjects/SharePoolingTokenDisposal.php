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
        public readonly FiatAmount $disposalProceeds,
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
            $this->disposalProceeds,
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
            disposalProceeds: $this->disposalProceeds,
            sameDayQuantityBreakdown: new QuantityBreakdown(),
            thirtyDayQuantityBreakdown: new QuantityBreakdown(),
            processed: false,
        ))->setPosition($this->position);
    }

    public function hasSameDayQuantity(): bool
    {
        return $this->sameDayQuantity()->isGreaterThan('0');
    }

    public function sameDayQuantity(): Quantity
    {
        return $this->sameDayQuantityBreakdown->quantity();
    }

    public function hasThirtyDayQuantity(): bool
    {
        return $this->thirtyDayQuantity()->isGreaterThan('0');
    }

    public function thirtyDayQuantity(): Quantity
    {
        return $this->thirtyDayQuantityBreakdown->quantity();
    }

    public function hasSection104PoolQuantity(): bool
    {
        return $this->section104PoolQuantity()->isGreaterThan('0');
    }

    public function section104PoolQuantity(): Quantity
    {
        return $this->quantity->minus($this->sameDayQuantity()->plus($this->thirtyDayQuantity()));
    }

    public function hasAvailableSameDayQuantity(): bool
    {
        return $this->availableSameDayQuantity()->isGreaterThan('0');
    }

    public function availableSameDayQuantity(): Quantity
    {
        return $this->quantity->minus($this->sameDayQuantity());
    }

    public function hasAvailableThirtyDayQuantity(): bool
    {
        return $this->availableThirtyDayQuantity()->isGreaterThan('0');
    }

    public function availableThirtyDayQuantity(): Quantity
    {
        // Same-day quantity always gets priority, and it is assumed that the existing 30-
        // day quantity has already been matched with acquisitions closest in time. That
        // leaves us with the current section 104 pool quantity, which is what we return
        return $this->section104PoolQuantity();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s: disposed of %s tokens for %s (cost basis: %s)',
            $this->date,
            $this->quantity,
            $this->disposalProceeds,
            $this->costBasis,
        );
    }
}
