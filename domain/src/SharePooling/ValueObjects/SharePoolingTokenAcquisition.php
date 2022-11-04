<?php

namespace Domain\SharePooling\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Services\Math\Math;
use Domain\Tests\SharePooling\Factories\ValueObjects\SharePoolingTokenAcquisitionFactory;
use Domain\ValueObjects\FiatAmount;

final class SharePoolingTokenAcquisition extends SharePoolingTransaction
{
    public string $sameDayQuantity;
    public string $thirtyDayQuantity;
    public string $section104PoolQuantity;

    public function __construct(
        public readonly LocalDate $date,
        public readonly string $quantity,
        public readonly FiatAmount $costBasis,
    ) {
        $this->sameDayQuantity = '0';
        $this->thirtyDayQuantity = '0';
        // Acquisitions always assume that the whole quantity goes to the section
        // 104 pool. It is subsequent disposals (or the disposals being replayed
        // after being reverted) that will update the acquisitions' quantities.
        $this->section104PoolQuantity = $quantity;
    }

    protected static function newFactory(): SharePoolingTokenAcquisitionFactory
    {
        return SharePoolingTokenAcquisitionFactory::new();
    }

    public function hasSection104PoolQuantity(): bool
    {
        return Math::gt($this->section104PoolQuantity, '0');
    }

    public function section104PoolCostBasis(): FiatAmount
    {
        return $this->costBasis->dividedBy($this->quantity)->multipliedBy($this->section104PoolQuantity);
    }

    /**
     * Increase the same-day quantity and adjust the section 104 pool quantity accordingly.
     *
     * @return string The remaining quantity
     */
    public function increaseSameDayQuantity(string $quantity): string
    {
        $quantityToApply = Math::min($quantity, $this->section104PoolQuantity);

        $this->sameDayQuantity = Math::add($this->sameDayQuantity, $quantityToApply);
        $this->section104PoolQuantity = Math::sub($this->section104PoolQuantity, $quantityToApply);

        return Math::sub($quantity, $quantityToApply);
    }

    public function __toString(): string
    {
        return sprintf('%s: acquired %s tokens for %s', $this->date, $this->quantity, $this->costBasis);
    }
}
