<?php

namespace Domain\SharePooling\ValueObjects;

use Brick\DateTime\LocalDate;
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

    public function hasSameDayQuantity(): bool
    {
        return $this->sameDayQuantity->isGreaterThan('0');
    }

    public function sameDayQuantity(): Quantity
    {
        return $this->sameDayQuantity;
    }

    public function hasThirtyDayQuantity(): bool
    {
        return $this->thirtyDayQuantity->isGreaterThan('0');
    }

    public function thirtyDayQuantity(): Quantity
    {
        return $this->thirtyDayQuantity;
    }

    public function hasSection104PoolQuantity(): bool
    {
        return $this->section104PoolQuantity()->isGreaterThan('0');
    }

    public function section104PoolQuantity(): Quantity
    {
        return $this->quantity->minus($this->sameDayQuantity->plus($this->thirtyDayQuantity));
    }

    public function hasAvailableSameDayQuantity(): bool
    {
        return $this->availableSameDayQuantity()->isGreaterThan('0');
    }

    public function availableSameDayQuantity(): Quantity
    {
        return $this->quantity->minus($this->sameDayQuantity);
    }

    public function hasAvailableThirtyDayQuantity(): bool
    {
        return $this->availableThirtyDayQuantity()->isGreaterThan('0');
    }

    public function availableThirtyDayQuantity(): Quantity
    {
        // Same-day quantity always gets priority, and it is assumed that the existing
        // 30-day quantity has already been matched with older disposals. That leaves
        // us with the current section 104 pool quantity, which is what we return
        return $this->section104PoolQuantity();
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

    public function decreaseSameDayQuantity(Quantity $quantity): void
    {
        if ($quantity->isGreaterThan($this->sameDayQuantity)) {
            // @TODO move to proper exception
            throw new \Exception(sprintf(
                'Cannot decrease same-day quantity by %s: only %s available',
                $quantity,
                $this->sameDayQuantity,
            ));
        }

        $this->sameDayQuantity = $this->sameDayQuantity->minus(($quantity));
    }

    /**
     * Increase the 30-day quantity.
     *
     * @return Quantity The remaining quantity
     */
    public function increaseThirtyDayQuantity(Quantity $quantity): Quantity
    {
        $quantityToAdd = Quantity::minimum($quantity, $this->availableThirtyDayQuantity());
        $this->thirtyDayQuantity = $this->thirtyDayQuantity->plus($quantityToAdd);

        // Return remaining quantity
        return $quantity->minus($quantityToAdd);
    }

    public function decreaseThirtyDayQuantity(Quantity $quantity): void
    {
        if ($quantity->isGreaterThan($this->thirtyDayQuantity)) {
            // @TODO move to proper exception
            throw new \Exception(sprintf(
                'Cannot decrease 30-day quantity by %s: only %s available',
                $quantity,
                $this->thirtyDayQuantity,
            ));
        }

        $this->thirtyDayQuantity = $this->thirtyDayQuantity->minus(($quantity));
    }

    public function __toString(): string
    {
        return sprintf('%s: acquired %s tokens for %s', $this->date, $this->quantity, $this->costBasis);
    }
}
