<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Entities;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Entities\Exceptions\SharePoolingAssetAcquisitionException;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\Tests\Aggregates\SharePoolingAsset\Factories\Entities\SharePoolingAssetAcquisitionFactory;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

final class SharePoolingAssetAcquisition extends SharePoolingAssetTransaction
{
    private Quantity $sameDayQuantity;

    private Quantity $thirtyDayQuantity;

    public function __construct(
        LocalDate $date,
        Quantity $quantity,
        FiatAmount $costBasis,
        public readonly bool $forFiat,
        ?SharePoolingAssetTransactionId $id = null,
        ?Quantity $sameDayQuantity = null,
        ?Quantity $thirtyDayQuantity = null,
    ) {
        parent::__construct($date, $quantity, $costBasis, id: $id, processed: true);

        $this->sameDayQuantity = $sameDayQuantity ?? Quantity::zero();
        $this->thirtyDayQuantity = $thirtyDayQuantity ?? Quantity::zero();

        $allocatedQuantity = $this->sameDayQuantity->plus($this->thirtyDayQuantity);

        $this->quantity->isGreaterThanOrEqualTo($allocatedQuantity)
            || throw SharePoolingAssetAcquisitionException::excessiveQuantityAllocated($this->quantity, $allocatedQuantity);
    }

    /** @return SharePoolingAssetAcquisitionFactory<static> */
    protected static function newFactory(): SharePoolingAssetAcquisitionFactory
    {
        return SharePoolingAssetAcquisitionFactory::new();
    }

    public function averageCostBasisPerUnit(): FiatAmount
    {
        return $this->costBasis->dividedBy($this->quantity);
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

    public function increaseSameDayQuantity(Quantity $quantity): self
    {
        if ($quantity->isGreaterThan($availableQuantity = $this->section104PoolQuantity())) {
            throw SharePoolingAssetAcquisitionException::insufficientSameDayQuantityToIncrease($quantity, $availableQuantity);
        }

        $this->sameDayQuantity = $this->sameDayQuantity->plus($quantity);

        return $this;
    }

    /**
     * Increase the same-day quantity and adjust the 30-day quantity accordingly.
     *
     * @return Quantity the added quantity
     */
    public function increaseSameDayQuantityUpToAvailableQuantity(Quantity $quantity): Quantity
    {
        // Adjust same-day quantity
        $quantityToAdd = Quantity::minimum($quantity, $this->availableSameDayQuantity());
        $this->sameDayQuantity = $this->sameDayQuantity->plus($quantityToAdd);

        // Adjust 30-day quantity
        $quantityToDeduct = Quantity::minimum($quantityToAdd, $this->thirtyDayQuantity);
        $this->thirtyDayQuantity = $this->thirtyDayQuantity->minus($quantityToDeduct);

        return $quantityToAdd;
    }

    /** @throws SharePoolingAssetAcquisitionException */
    public function decreaseSameDayQuantity(Quantity $quantity): self
    {
        if ($quantity->isGreaterThan($this->sameDayQuantity)) {
            throw SharePoolingAssetAcquisitionException::insufficientSameDayQuantityToDecrease($quantity, $this->sameDayQuantity);
        }

        $this->sameDayQuantity = $this->sameDayQuantity->minus($quantity);

        return $this;
    }

    public function increaseThirtyDayQuantity(Quantity $quantity): self
    {
        if ($quantity->isGreaterThan($availableQuantity = $this->section104PoolQuantity())) {
            throw SharePoolingAssetAcquisitionException::insufficientThirtyDayQuantityToIncrease($quantity, $availableQuantity);
        }

        $this->thirtyDayQuantity = $this->thirtyDayQuantity->plus($quantity);

        return $this;
    }

    /** @return Quantity the added quantity */
    public function increaseThirtyDayQuantityUpToAvailableQuantity(Quantity $quantity): Quantity
    {
        $quantityToAdd = Quantity::minimum($quantity, $this->availableThirtyDayQuantity());
        $this->thirtyDayQuantity = $this->thirtyDayQuantity->plus($quantityToAdd);

        return $quantityToAdd;
    }

    /** @throws SharePoolingAssetAcquisitionException */
    public function decreaseThirtyDayQuantity(Quantity $quantity): self
    {
        if ($quantity->isGreaterThan($this->thirtyDayQuantity)) {
            throw SharePoolingAssetAcquisitionException::insufficientThirtyDayQuantityToDecrease($quantity, $this->thirtyDayQuantity);
        }

        $this->thirtyDayQuantity = $this->thirtyDayQuantity->minus($quantity);

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s: acquired %s tokens for %s (for fiat: %s)',
            $this->date,
            $this->quantity,
            $this->costBasis,
            $this->forFiat ? 'yes' : 'no',
        );
    }
}
