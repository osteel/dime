<?php

namespace Domain\SharePooling\ValueObjects;

use ArrayIterator;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int, SharePoolingTokenAcquisition> */
final class SharePoolingTokenAcquisitions implements IteratorAggregate
{
    /** @param array<int, SharePoolingTokenAcquisition> $acquisitions */
    private function __construct(private array $acquisitions = [])
    {
    }

    public static function make(SharePoolingTokenAcquisition ...$acquisitions): self
    {
        return new self(array_values($acquisitions));
    }

    /** @return Traversable<int, SharePoolingTokenAcquisition> */
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

    public function quantity(): Quantity
    {
        return array_reduce(
            $this->acquisitions,
            fn (Quantity $total, SharePoolingTokenAcquisition $acquisition) => $total->plus($acquisition->quantity),
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
            fn (FiatAmount $total, SharePoolingTokenAcquisition $acquisition) => $total->plus($acquisition->costBasis),
            $this->acquisitions[0]->costBasis->nilAmount(),
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
            fn (Quantity $total, SharePoolingTokenAcquisition $acquisition) => $total->plus($acquisition->section104PoolQuantity()),
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
            fn (SharePoolingTokenAcquisition $acquisition) => $acquisition->hasSection104PoolQuantity(),
        );

        if (empty($section104PoolAcquisitions)) {
            $this->acquisitions[0]->costBasis->nilAmount();
        }

        return array_reduce(
            $section104PoolAcquisitions,
            fn (FiatAmount $total, SharePoolingTokenAcquisition $acquisition) => $total->plus($acquisition->section104PoolCostBasis()),
            $this->acquisitions[0]->costBasis->nilAmount(),
        );
    }

    public function averageSection104PoolCostBasisPerUnit(): ?FiatAmount
    {
        return $this->section104PoolCostBasis()?->dividedBy($this->section104PoolQuantity());
    }

    public function withAvailableSameDayQuantity(): SharePoolingTokenAcquisitions
    {
        $acquisitions = array_filter(
            $this->acquisitions,
            fn (SharePoolingTokenAcquisition $acquisition) => $acquisition->hasAvailableSameDayQuantity(),
        );

        return self::make(...$acquisitions);
    }

    public function availableSameDayQuantity(): Quantity
    {
        return array_reduce(
            $this->acquisitions,
            fn (Quantity $total, SharePoolingTokenAcquisition $acquisition) => $total->plus($acquisition->availableSameDayQuantity()),
            Quantity::zero(),
        );
    }

    public function withAvailableThirtyDayQuantity(): SharePoolingTokenAcquisitions
    {
        $acquisitions = array_filter(
            $this->acquisitions,
            fn (SharePoolingTokenAcquisition $acquisition) => $acquisition->hasAvailableThirtyDayQuantity(),
        );

        return self::make(...$acquisitions);
    }

    public function withThirtyDayQuantity(): SharePoolingTokenAcquisitions
    {
        $acquisitions = array_filter(
            $this->acquisitions,
            fn (SharePoolingTokenAcquisition $acquisition) => $acquisition->hasThirtyDayQuantity(),
        );

        return self::make(...$acquisitions);
    }
}
