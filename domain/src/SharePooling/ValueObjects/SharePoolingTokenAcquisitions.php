<?php

namespace Domain\SharePooling\ValueObjects;

use ArrayIterator;
use Domain\Services\Math\Math;
use Domain\ValueObjects\FiatAmount;
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

    public function quantity(): string
    {
        return array_reduce(
            $this->transactions,
            fn (string $total, SharePoolingTransaction $transaction) => Math::add($total, $transaction->quantity),
            '0'
        );
    }

    public function section104PoolQuantity(): string
    {
        return array_reduce(
            $this->transactions,
            fn (string $total, SharePoolingTokenAcquisition $transaction) => Math::add($total, $transaction->section104PoolQuantity),
            '0'
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
            fn (string $total, SharePoolingTokenAcquisition $acquisition) => Math::add($total, $acquisition->quantity),
            '0'
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
            fn (string $total, SharePoolingTokenAcquisition $acquisition) => Math::add($total, $acquisition->section104PoolQuantity),
            '0'
        );

        return $costBasis->dividedBy($quantity);
    }
}
