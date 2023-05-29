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

    /** Increase the same-day quantity and adjust the 30-day quantity accordingly. */
    public function increaseSameDayQuantity(Quantity $quantity): self
    {
        // Adjust same-day quantity
        $quantityToAdd = Quantity::minimum($quantity, $this->availableSameDayQuantity());
        $this->sameDayQuantity = $this->sameDayQuantity->plus($quantityToAdd);

        // Adjust 30-day quantity
        $quantityToDeduct = Quantity::minimum($quantityToAdd, $this->thirtyDayQuantity);
        $this->thirtyDayQuantity = $this->thirtyDayQuantity->minus($quantityToDeduct);

        return $this;
    }

    /** @throws SharePoolingAssetAcquisitionException */
    public function decreaseSameDayQuantity(Quantity $quantity): self
    {
        if ($quantity->isGreaterThan($this->sameDayQuantity)) {
            throw SharePoolingAssetAcquisitionException::insufficientSameDayQuantity($quantity, $this->sameDayQuantity);
        }

        $this->sameDayQuantity = $this->sameDayQuantity->minus($quantity);

        return $this;
    }

    public function increaseThirtyDayQuantity(Quantity $quantity): self
    {
        $quantityToAdd = Quantity::minimum($quantity, $this->availableThirtyDayQuantity());
        $this->thirtyDayQuantity = $this->thirtyDayQuantity->plus($quantityToAdd);

        return $this;
    }

    /** @throws SharePoolingAssetAcquisitionException */
    public function decreaseThirtyDayQuantity(Quantity $quantity): self
    {
        if ($quantity->isGreaterThan($this->thirtyDayQuantity)) {
            throw SharePoolingAssetAcquisitionException::insufficientThirtyDayQuantity($quantity, $this->thirtyDayQuantity);
        }

        $this->thirtyDayQuantity = $this->thirtyDayQuantity->minus($quantity);

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s: acquired %s tokens for %s', $this->date, $this->quantity, $this->costBasis);
    }
}
