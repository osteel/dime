<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\ValueObjects;

use ArrayIterator;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\Exceptions\SharePoolingAssetTransactionException;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int,SharePoolingAssetAcquisition> */
final class SharePoolingAssetAcquisitions implements IteratorAggregate
{
    /** @param array<int,SharePoolingAssetAcquisition> $acquisitions */
    private function __construct(private array $acquisitions = [])
    {
    }

    public static function make(SharePoolingAssetAcquisition ...$acquisitions): self
    {
        return new self(array_values($acquisitions));
    }

    /** @return Traversable<int, SharePoolingAssetAcquisition> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->acquisitions);
    }

    public function isEmpty(): bool
    {
        return empty($this->acquisitions);
    }

    public function count(): int
    {
        return count($this->acquisitions);
    }

    public function add(SharePoolingAssetAcquisition ...$acquisitions): self
    {
        foreach ($acquisitions as $acquisition) {
            try {
                $acquisition->setPosition($this->count());
            } catch (SharePoolingAssetTransactionException) {
            }

            $position = $acquisition->getPosition();

            assert(! is_null($position));

            $this->acquisitions[$position] = $acquisition;
        }

        return $this;
    }

    public function quantity(): Quantity
    {
        return array_reduce(
            $this->acquisitions,
            fn (Quantity $total, SharePoolingAssetAcquisition $acquisition) => $total->plus($acquisition->quantity),
            Quantity::zero(),
        );
    }

    public function costBasis(): ?FiatAmount
    {
        if ($this->isEmpty()) {
            return null;
        }

        return array_reduce(
            $this->acquisitions,
            fn (FiatAmount $total, SharePoolingAssetAcquisition $acquisition) => $total->plus($acquisition->costBasis),
            $this->acquisitions[0]->costBasis->zero(),
        );
    }

    public function averageCostBasisPerUnit(): ?FiatAmount
    {
        return $this->costBasis()?->dividedBy($this->quantity());
    }

    public function section104PoolQuantity(): Quantity
    {
        return array_reduce(
            $this->acquisitions,
            fn (Quantity $total, SharePoolingAssetAcquisition $acquisition) => $total->plus($acquisition->section104PoolQuantity()),
            Quantity::zero(),
        );
    }

    public function section104PoolCostBasis(): ?FiatAmount
    {
        if ($this->isEmpty()) {
            return null;
        }

        $section104PoolAcquisitions = array_filter(
            $this->acquisitions,
            fn (SharePoolingAssetAcquisition $acquisition) => $acquisition->hasSection104PoolQuantity(),
        );

        if (empty($section104PoolAcquisitions)) {
            $this->acquisitions[0]->costBasis->zero();
        }

        return array_reduce(
            $section104PoolAcquisitions,
            fn (FiatAmount $total, SharePoolingAssetAcquisition $acquisition) => $total->plus($acquisition->section104PoolCostBasis()),
            $this->acquisitions[0]->costBasis->zero(),
        );
    }

    public function averageSection104PoolCostBasisPerUnit(): ?FiatAmount
    {
        return $this->section104PoolCostBasis()?->dividedBy($this->section104PoolQuantity());
    }

    public function withAvailableSameDayQuantity(): SharePoolingAssetAcquisitions
    {
        $acquisitions = array_filter(
            $this->acquisitions,
            fn (SharePoolingAssetAcquisition $acquisition) => $acquisition->hasAvailableSameDayQuantity(),
        );

        return self::make(...$acquisitions);
    }

    public function availableSameDayQuantity(): Quantity
    {
        return array_reduce(
            $this->acquisitions,
            fn (Quantity $total, SharePoolingAssetAcquisition $acquisition) => $total->plus($acquisition->availableSameDayQuantity()),
            Quantity::zero(),
        );
    }

    public function withAvailableThirtyDayQuantity(): SharePoolingAssetAcquisitions
    {
        $acquisitions = array_filter(
            $this->acquisitions,
            fn (SharePoolingAssetAcquisition $acquisition) => $acquisition->hasAvailableThirtyDayQuantity(),
        );

        return self::make(...$acquisitions);
    }

    public function withThirtyDayQuantity(): SharePoolingAssetAcquisitions
    {
        $acquisitions = array_filter(
            $this->acquisitions,
            fn (SharePoolingAssetAcquisition $acquisition) => $acquisition->hasThirtyDayQuantity(),
        );

        return self::make(...$acquisitions);
    }
}
