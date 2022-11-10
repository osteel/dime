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
        public readonly Quantity $sameDayQuantity,
        public readonly Quantity $thirtyDayQuantity,
        public readonly Quantity $section104PoolQuantity,
        public readonly bool $processed = true,
    ) {
    }

    protected static function newFactory(): SharePoolingTokenDisposalFactory
    {
        return SharePoolingTokenDisposalFactory::new();
    }

    public function hasAvailableSameDayQuantity(): bool
    {
        return $this->quantity->isGreaterThan($this->sameDayQuantity);
    }

    public function hasSection104PoolQuantity(): bool
    {
        return $this->section104PoolQuantity->isGreaterThan('0');
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
            sameDayQuantity: Quantity::zero(),
            thirtyDayQuantity: Quantity::zero(),
            section104PoolQuantity: $this->quantity,
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
