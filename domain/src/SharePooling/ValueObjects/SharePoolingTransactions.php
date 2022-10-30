<?php

namespace Domain\SharePooling\ValueObjects;

use ArrayIterator;
use Brick\DateTime\LocalDate;
use Domain\SharePooling\ValueObjects\SharePoolingAcquisition;
use Domain\SharePooling\ValueObjects\SharePoolingDisposal;
use Domain\Services\Math\Math;
use Domain\ValueObjects\FiatAmount;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int, SharePoolingTransaction> */
final class SharePoolingTransactions implements IteratorAggregate
{
    /** @param array<int, SharePoolingTransaction> $transactions */
    private function __construct(private array $transactions = [])
    {
    }

    public static function make(SharePoolingTransaction ...$transactions): self
    {
        return new self(array_values($transactions));
    }

    /** @return Traversable<int, SharePoolingTransaction> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->transactions);
    }

    public function copy(): SharePoolingTransactions
    {
        return new self($this->transactions);
    }

    public function isEmpty(): bool
    {
        return empty($this->transactions);
    }

    public function count(): int
    {
        return count($this->transactions);
    }

    public function first(): ?SharePoolingTransaction
    {
        return $this->transactions[0] ?? null;
    }

    public function reverse(): SharePoolingTransactions
    {
        return new self(array_reverse($this->transactions));
    }

    public function add(SharePoolingTransaction $transaction): self
    {
        $this->transactions[] = $transaction;

        return $this;
    }

    public function quantity(): string
    {
        return array_reduce(
            $this->transactions,
            fn (string $total, SharePoolingTransaction $transaction) => $transaction instanceof SharePoolingAcquisition
                ? Math::add($total, $transaction->quantity)
                : Math::sub($total, $transaction->quantity),
            '0'
        );
    }

    public function averageSection104PoolCostBasisPerUnit(): ?FiatAmount
    {
        $section104PoolAcquisitions = array_filter(
            $this->transactions,
            fn (SharePoolingTransaction $transaction) => $transaction instanceof SharePoolingAcquisition
                && $transaction->hasSection104PoolQuantity(),
        );

        if (empty($section104PoolAcquisitions)) {
            return null;
        }

        $costBasis = array_reduce(
            $section104PoolAcquisitions,
            fn (FiatAmount $total, SharePoolingAcquisition $acquisition) => $total->plus($acquisition->section104PoolCostBasis()),
            new FiatAmount('0', $section104PoolAcquisitions[0]->costBasis->currency),
        );

        $quantity = array_reduce(
            $section104PoolAcquisitions,
            fn (string $total, SharePoolingAcquisition $acquisition) => Math::add($total, $acquisition->quantity),
            '0'
        );

        return $costBasis->dividedBy($quantity));
    }

    public function transactionsMadeOn(LocalDate $date, ?string $type = null): SharePoolingTransactions
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTransaction $transaction) => $transaction->date->isEqualTo($date)
                && (is_null($type) ? true : $transaction instanceof $type)
        );

        return new self($transactions);
    }

    public function acquisitionsMadeOn(LocalDate $date): SharePoolingTransactions
    {
        return $this->transactionsMadeOn($date, SharePoolingAcquisition::class);
    }

    public function disposalsMadeOn(LocalDate $date): SharePoolingTransactions
    {
        return $this->transactionsMadeOn($date, SharePoolingDisposal::class);
    }

    public function transactionsMadeBetween(
        LocalDate $from,
        LocalDate $to,
        ?string $type = null,
    ): SharePoolingTransactions {
        if ($from->isEqualTo($to)) {
            return $this->transactionsMadeOn($from, $type);
        }

        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTransaction $transaction) =>
                $transaction->date->isAfterOrEqualTo($from->isBefore($to) ? $from : $to)
                && $transaction->date->isBeforeOrEqualTo($to->isAfter($from) ? $to : $from)
                && (is_null($type) ? true : $transaction instanceof $type)
        );

        return new self($transactions);
    }

    public function acquisitionsMadeBetween(LocalDate $from, LocalDate $to): SharePoolingTransactions
    {
        return $this->transactionsMadeBetween($from, $to, SharePoolingAcquisition::class);
    }

    public function disposalsMadeBetween(LocalDate $from, LocalDate $to): SharePoolingTransactions
    {
        return $this->transactionsMadeBetween($from, $to, SharePoolingDisposal::class);
    }

    public function transactionsMadeBefore(LocalDate $date, ?string $type = null): SharePoolingTransactions
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTransaction $transaction) => $transaction->date->isBefore($date)
                && (is_null($type) ? true : $transaction instanceof $type)
        );

        return new self($transactions);
    }

    public function acquisitionsMadeBefore(LocalDate $date): SharePoolingTransactions
    {
        return $this->transactionsMadeBefore($date, SharePoolingAcquisition::class);
    }

    public function disposalsMadeBefore(LocalDate $date): SharePoolingTransactions
    {
        return $this->transactionsMadeBefore($date, SharePoolingDisposal::class);
    }
}
