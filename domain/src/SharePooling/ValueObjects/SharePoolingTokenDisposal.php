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
        public readonly QuantityBreakdown $sameDayQuantity,
        public readonly QuantityBreakdown $thirtyDayQuantity,
        public readonly bool $processed = true,
    ) {
    }

    protected static function newFactory(): SharePoolingTokenDisposalFactory
    {
        return SharePoolingTokenDisposalFactory::new();
    }

    public function hasAvailableSameDayQuantity(): bool
    {
        return $this->quantity->isGreaterThan($this->sameDayQuantity->getQuantity());
    }

    public function has30DayQuantity(): bool
    {
        return $this->thirtyDayQuantity->getQuantity()->isGreaterThan('0');
    }

    public function hasSection104PoolQuantity(): bool
    {
        return $this->quantity->isGreaterThan(
            $this->sameDayQuantity->getQuantity()->plus($this->thirtyDayQuantity->getQuantity()),
        );
    }

    public function getSection104PoolQuantity(): Quantity
    {
        return $this->quantity->minus(
            $this->sameDayQuantity->getQuantity()->plus($this->thirtyDayQuantity->getQuantity()),
        );
    }

    /**
     * Return a copy of the disposal with reset quantities and marked as unprocessed.
     */
    public function copyAsUnprocessed(): SharePoolingTokenDisposal
    {
        return (new SharePoolingTokenDisposal(
            date: $this->date,
            quantity: $this->quantity,
            costBasis: $this->costBasis->nilAmount(),
            disposalProceeds: $this->disposalProceeds,
            sameDayQuantity: new QuantityBreakdown(),
            thirtyDayQuantity: new QuantityBreakdown(),
            processed: false,
        ))->setPosition($this->position);
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
