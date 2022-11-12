<?php

namespace Domain\SharePooling\ValueObjects;

use ArrayIterator;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int, SharePoolingTransaction> */
final class SharePoolingTokenAcquisitions implements IteratorAggregate
{
    /** @param array<int, SharePoolingTokenAcquisition> $transactions */
    private function __construct(private array $transactions = [])
    {
    }

    public static function make(SharePoolingTokenAcquisition ...$transactions): self
    {
        return new self(array_values($transactions));
    }

    /** @return Traversable<int, SharePoolingTokenAcquisition> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->transactions);
    }

    public function isEmpty(): bool
    {
        return empty($this->transactions);
    }

    public function count(): int
    {
        return count($this->transactions);
    }

    public function quantity(): Quantity
    {
        return array_reduce(
            $this->transactions,
            fn (Quantity $total, SharePoolingTransaction $transaction) => $total->plus($transaction->quantity),
            Quantity::zero(),
        );
    }

    public function costBasis(): ?FiatAmount
    {
        if ($this->isEmpty()) {
            return null;
        }

        return array_reduce(
            $this->transactions,
            fn (FiatAmount $total, SharePoolingTokenAcquisition $acquisition) => $total->plus($acquisition->costBasis),
            $this->transactions[0]->costBasis->nilAmount(),
        );
    }

    public function averageCostBasisPerUnit(): ?FiatAmount
    {
        return $this->costBasis()?->dividedBy($this->quantity());
    }

    public function section104PoolQuantity(): Quantity
    {
        return array_reduce(
            $this->transactions,
            fn (Quantity $total, SharePoolingTokenAcquisition $transaction) => $total->plus($transaction->section104PoolQuantity()),
            Quantity::zero(),
        );
    }

    public function section104PoolCostBasis(): ?FiatAmount
    {
        if ($this->isEmpty()) {
            return null;
        }

        $section104PoolAcquisitions = array_filter(
            $this->transactions,
            fn (SharePoolingTokenAcquisition $transaction) => $transaction->hasSection104PoolQuantity(),
        );

        if (empty($section104PoolAcquisitions)) {
            $this->transactions[0]->costBasis->nilAmount();
        }

        return array_reduce(
            $section104PoolAcquisitions,
            fn (FiatAmount $total, SharePoolingTokenAcquisition $acquisition) => $total->plus($acquisition->costBasis),
            $this->transactions[0]->costBasis->nilAmount(),
        );
    }

    public function averageSection104PoolCostBasisPerUnit(): ?FiatAmount
    {
        return $this->section104PoolCostBasis()?->dividedBy($this->section104PoolQuantity());
    }

    public function availableSameDayQuantity(): Quantity
    {
        return array_reduce(
            $this->transactions,
            fn (Quantity $total, SharePoolingTokenAcquisition $transaction) => $total->plus($transaction->availableSameDayQuantity()),
            Quantity::zero(),
        );
    }

    public function withAvailableSameDayQuantity(): SharePoolingTokenAcquisitions
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTokenAcquisition $transaction) => $transaction->hasAvailableSameDayQuantity(),
        );

        return self::make(...$transactions);
    }

    public function withAvailableThirtyDayQuantity(): SharePoolingTokenAcquisitions
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTokenAcquisition $transaction) => $transaction->hasAvailableThirtyDayQuantity(),
        );

        return self::make(...$transactions);
    }

    public function withThirtyDayQuantity(): SharePoolingTokenAcquisitions
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTokenAcquisition $transaction) => $transaction->hasThirtyDayQuantity(),
        );

        return self::make(...$transactions);
    }
}
