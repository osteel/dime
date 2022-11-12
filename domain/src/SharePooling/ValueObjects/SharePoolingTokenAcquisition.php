<?php

namespace Domain\SharePooling\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\SharePooling\ValueObjects\Exceptions\SharePoolingTokenAcquisitionException;
use Domain\Tests\SharePooling\Factories\ValueObjects\SharePoolingTokenAcquisitionFactory;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

final class SharePoolingTokenAcquisition extends SharePoolingTransaction
{
    public function __construct(
        public readonly LocalDate $date,
        public readonly Quantity $quantity,
        public readonly FiatAmount $costBasis,
        private ?Quantity $sameDayQuantity = null,
        private ?Quantity $thirtyDayQuantity = null,
    ) {
        $this->sameDayQuantity = $sameDayQuantity ?? Quantity::zero();
        $this->thirtyDayQuantity = $thirtyDayQuantity ?? Quantity::zero();
    }

    protected static function newFactory(): SharePoolingTokenAcquisitionFactory
    {
        return SharePoolingTokenAcquisitionFactory::new();
    }

    public function copy(): static
    {
        return (new self(
            $this->date,
            $this->quantity,
            $this->costBasis,
            $this->sameDayQuantity,
            $this->thirtyDayQuantity,
        ))->setPosition($this->position);
    }

    public function sameDayQuantity(): Quantity
    {
        return $this->sameDayQuantity;
    }

    public function thirtyDayQuantity(): Quantity
    {
        return $this->thirtyDayQuantity;
    }

    public function section104PoolCostBasis(): FiatAmount
    {
        return $this->costBasis->dividedBy($this->quantity)->multipliedBy($this->section104PoolQuantity());
    }

    /**
     * Increase the same-day quantity and adjust the 30-day quantity accordingly.
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

        // Return remaining quantity
        return $quantity->minus($quantityToAdd);
    }

    /** @throws SharePoolingTokenAcquisitionException */
    public function decreaseSameDayQuantity(Quantity $quantity): void
    {
        if ($quantity->isGreaterThan($this->sameDayQuantity)) {
            throw SharePoolingTokenAcquisitionException::insufficientSameDayQuantity($quantity, $this->sameDayQuantity);
        }

        $this->sameDayQuantity = $this->sameDayQuantity->minus(($quantity));
    }

    /** @return Quantity The remaining quantity */
    public function increaseThirtyDayQuantity(Quantity $quantity): Quantity
    {
        $quantityToAdd = Quantity::minimum($quantity, $this->availableThirtyDayQuantity());
        $this->thirtyDayQuantity = $this->thirtyDayQuantity->plus($quantityToAdd);

        // Return remaining quantity
        return $quantity->minus($quantityToAdd);
    }

    /** @throws SharePoolingTokenAcquisitionException */
    public function decreaseThirtyDayQuantity(Quantity $quantity): void
    {
        if ($quantity->isGreaterThan($this->thirtyDayQuantity)) {
            throw SharePoolingTokenAcquisitionException::insufficientThirtyDayQuantity($quantity, $this->thirtyDayQuantity);
        }

        $this->thirtyDayQuantity = $this->thirtyDayQuantity->minus(($quantity));
    }

    public function __toString(): string
    {
        return sprintf('%s: acquired %s tokens for %s', $this->date, $this->quantity, $this->costBasis);
    }
}
