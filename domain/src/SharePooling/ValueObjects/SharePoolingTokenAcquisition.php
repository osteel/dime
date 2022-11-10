<?php

namespace Domain\SharePooling\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Tests\SharePooling\Factories\ValueObjects\SharePoolingTokenAcquisitionFactory;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

final class SharePoolingTokenAcquisition extends SharePoolingTransaction
{
    public Quantity $sameDayQuantity;
    public Quantity $thirtyDayQuantity;
    public Quantity $section104PoolQuantity;
    public readonly bool $processed;

    public function __construct(
        public readonly LocalDate $date,
        public readonly Quantity $quantity,
        public readonly FiatAmount $costBasis,
    ) {
        $this->sameDayQuantity = Quantity::zero();
        $this->thirtyDayQuantity = Quantity::zero();
        // Acquisitions always assume that the whole quantity goes to the section
        // 104 pool. It is subsequent disposals (or the disposals being replayed
        // after being reverted) that will update the acquisitions' quantities.
        $this->section104PoolQuantity = new Quantity($quantity->quantity);
        $this->processed = true;
    }

    protected static function newFactory(): SharePoolingTokenAcquisitionFactory
    {
        return SharePoolingTokenAcquisitionFactory::new();
    }

    public function hasAvailableSameDayQuantity(): bool
    {
        return $this->quantity->isGreaterThan($this->sameDayQuantity);
    }

    public function availableSameDayQuantity(): Quantity
    {
        return $this->quantity->minus($this->sameDayQuantity);
    }

    public function has30DayQuantity(): bool
    {
        return $this->thirtyDayQuantity->isGreaterThan('0');
    }

    public function hasSection104PoolQuantity(): bool
    {
        return $this->section104PoolQuantity->isGreaterThan('0');
    }

    public function section104PoolCostBasis(): FiatAmount
    {
        return $this->costBasis->dividedBy($this->quantity)->multipliedBy($this->section104PoolQuantity);
    }

    /**
     * Increase the same-day quantity and adjust the 30-day and section 104 pool quantities accordingly.
     *
     * @return Quantity The remaining quantity
     */
    public function increaseSameDayQuantity(Quantity $quantity): Quantity
    {
        // Adjust same-day quantity
        $quantityToAdd = Quantity::minimum($quantity, $this->availableSameDayQuantity());
        $this->sameDayQuantity = $this->sameDayQuantity->plus($quantityToAdd);

        // Adjust 30-day quantity
        $quantityToDeduct = Quantity::minimum($quantityToAdd, $this->thirtyDayQuantity);
        $this->thirtyDayQuantity = $this->thirtyDayQuantity->minus($quantityToDeduct);

        // Adjust section 104 pool quantity
        $quantityToDeduct = $quantityToAdd->minus($quantityToDeduct);
        $this->section104PoolQuantity = $this->section104PoolQuantity->minus($quantityToDeduct);

        // Return remaining quantity
        return $quantity->minus($quantityToAdd);
    }

    public function __toString(): string
    {
        return sprintf('%s: acquired %s tokens for %s', $this->date, $this->quantity, $this->costBasis);
    }
}
