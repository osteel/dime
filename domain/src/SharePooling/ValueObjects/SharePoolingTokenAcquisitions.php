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

    public function section104PoolQuantity(): Quantity
    {
        return array_reduce(
            $this->transactions,
            fn (Quantity $total, SharePoolingTokenAcquisition $transaction) => $total->plus($transaction->section104PoolQuantity),
            Quantity::zero(),
        );
    }

    public function availableSameDayQuantity(): Quantity
    {
        return array_reduce(
            $this->transactions,
            fn (Quantity $total, SharePoolingTokenAcquisition $transaction) => $total->plus($transaction->availableSameDayQuantity()),
            Quantity::zero(),
        );
    }

    public function averageCostBasisPerUnit(): ?FiatAmount
    {
        if ($this->isEmpty()) {
            return null;
        }

        $costBasis = array_reduce(
            $this->transactions,
            fn (FiatAmount $total, SharePoolingTokenAcquisition $acquisition) => $total->plus($acquisition->costBasis),
            $this->transactions[0]->costBasis->nilAmount(),
        );

        $quantity = array_reduce(
            $this->transactions,
            fn (Quantity $total, SharePoolingTokenAcquisition $acquisition) => $total->plus($acquisition->quantity),
            Quantity::zero(),
        );

        return $costBasis->dividedBy($quantity);
    }

    public function section104PoolAverageCostBasisPerUnit(): ?FiatAmount
    {
        $section104PoolAcquisitions = array_filter(
            $this->transactions,
            fn (SharePoolingTokenAcquisition $transaction) => $transaction->hasSection104PoolQuantity(),
        );

        if (empty($section104PoolAcquisitions)) {
            return null;
        }

        $costBasis = array_reduce(
            $section104PoolAcquisitions,
            fn (FiatAmount $total, SharePoolingTokenAcquisition $acquisition) => $total->plus($acquisition->section104PoolCostBasis()),
            $section104PoolAcquisitions[0]->costBasis->nilAmount(),
        );

        $quantity = array_reduce(
            $section104PoolAcquisitions,
            fn (Quantity $total, SharePoolingTokenAcquisition $acquisition) => $total->plus($acquisition->section104PoolQuantity),
            Quantity::zero(),
        );

        return $costBasis->dividedBy($quantity);
    }

    public function withAvailableSameDayQuantity(): SharePoolingTokenAcquisitions
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTokenAcquisition $transaction) => $transaction->hasAvailableSameDayQuantity(),
        );

        return self::make(...$transactions);
    }

    public function with30DayQuantity(): SharePoolingTokenAcquisitions
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTokenAcquisition $transaction) => $transaction->has30DayQuantity(),
        );

        return self::make(...$transactions);
    }

    public function upToTransaction(SharePoolingTokenAcquisition $acquisition): SharePoolingTokenAcquisitions
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTokenAcquisition $transaction) => $transaction->getPosition() < $acquisition->getPosition(),
        );

        return self::make(...$transactions);
    }
}