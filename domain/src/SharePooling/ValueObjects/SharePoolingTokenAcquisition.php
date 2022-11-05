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

    public function __construct(
        public readonly LocalDate $date,
        public readonly Quantity $quantity,
        public readonly FiatAmount $costBasis,
    ) {
        $this->sameDayQuantity = new Quantity('0');
        $this->thirtyDayQuantity = new Quantity('0');
        // Acquisitions always assume that the whole quantity goes to the section
        // 104 pool. It is subsequent disposals (or the disposals being replayed
        // after being reverted) that will update the acquisitions' quantities.
        $this->section104PoolQuantity = new Quantity($quantity->quantity);
    }

    protected static function newFactory(): SharePoolingTokenAcquisitionFactory
    {
        return SharePoolingTokenAcquisitionFactory::new();
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
     * Increase the same-day quantity and adjust the section 104 pool quantity accordingly.
     *
     * @return Quantity The remaining quantity
     */
    public function increaseSameDayQuantity(Quantity $quantity): Quantity
    {
        $quantityToApply = Quantity::minimum($quantity, $this->section104PoolQuantity);

        $this->sameDayQuantity = $this->sameDayQuantity->plus($quantityToApply);
        $this->section104PoolQuantity = $this->section104PoolQuantity->minus($quantityToApply);

        return $quantity->minus($quantityToApply);
    }

    public function __toString(): string
    {
        return sprintf('%s: acquired %s tokens for %s', $this->date, $this->quantity, $this->costBasis);
    }
}
