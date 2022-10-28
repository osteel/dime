<?php

namespace Domain\Section104Pool\ValueObjects;

use ArrayIterator;
use Brick\DateTime\LocalDate;
use Domain\Section104Pool\ValueObjects\Section104PoolAcquisition;
use Domain\Section104Pool\ValueObjects\Section104PoolDisposal;
use Domain\Services\Math\Math;
use Domain\ValueObjects\FiatAmount;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int, Section104PoolTransaction> */
final class Section104PoolTransactions implements IteratorAggregate
{
    /** @param array<int, Section104PoolTransaction> $transactions */
    private function __construct(private array $transactions = [])
    {
    }

    public static function make(Section104PoolTransaction ...$transactions): self
    {
        return new self(array_values($transactions));
    }

    /** @return Traversable<int, Section104PoolTransaction> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->transactions);
    }

    public function copy(): Section104PoolTransactions
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

    public function first(): ?Section104PoolTransaction
    {
        return $this->transactions[0] ?? null;
    }

    public function reverse(): Section104PoolTransactions
    {
        return new self(array_reverse($this->transactions));
    }

    public function add(Section104PoolTransaction $transaction): self
    {
        $this->transactions[] = $transaction;

        return $this;
    }

    public function quantity(): string
    {
        return array_reduce(
            $this->transactions,
            fn (string $total, Section104PoolTransaction $transaction) => Math::add($total, $transaction->quantity),
            '0'
        );
    }

    public function costBasis(): ?FiatAmount
    {
        if (empty($this->transactions)) {
            return null;
        }

        $transactions = $this->transactions;
        $first = array_shift($transactions);

        return array_reduce(
            $transactions,
            fn (FiatAmount $total, Section104PoolTransaction $transaction) => $total->plus($transaction->costBasis),
            $first->costBasis,
        );
    }

    public function averageCostBasisPerUnit(): ?FiatAmount
    {
        if (is_null($costBasis = $this->costBasis())) {
            return null;
        }

        return $costBasis->dividedBy($this->quantity());
    }

    public function transactionsMadeOn(LocalDate $date, ?string $type = null): Section104PoolTransactions
    {
        $transactions = array_filter(
            $this->transactions,
            fn (Section104PoolTransaction $transaction) => $transaction->date->isEqualTo($date)
                && (is_null($type) ? true : $transaction instanceof $type)
        );

        return new self($transactions);
    }

    public function acquisitionsMadeOn(LocalDate $date): Section104PoolTransactions
    {
        return $this->transactionsMadeOn($date, Section104PoolAcquisition::class);
    }

    public function disposalsMadeOn(LocalDate $date): Section104PoolTransactions
    {
        return $this->transactionsMadeOn($date, Section104PoolDisposal::class);
    }

    public function transactionsMadeBetween(
        LocalDate $from,
        LocalDate $to,
        ?string $type = null,
    ): Section104PoolTransactions {
        if ($from->isEqualTo($to)) {
            return $this->transactionsMadeOn($from, $type);
        }

        $transactions = array_filter(
            $this->transactions,
            fn (Section104PoolTransaction $transaction) =>
                $transaction->date->isAfterOrEqualTo($from->isBefore($to) ? $from : $to)
                && $transaction->date->isBeforeOrEqualTo($to->isAfter($from) ? $to : $from)
                && (is_null($type) ? true : $transaction instanceof $type)
        );

        return new self($transactions);
    }

    public function acquisitionsMadeBetween(LocalDate $from, LocalDate $to): Section104PoolTransactions
    {
        return $this->transactionsMadeBetween($from, $to, Section104PoolAcquisition::class);
    }

    public function disposalsMadeBetween(LocalDate $from, LocalDate $to): Section104PoolTransactions
    {
        return $this->transactionsMadeBetween($from, $to, Section104PoolDisposal::class);
    }

    public function transactionsMadeBefore(LocalDate $date, ?string $type = null): Section104PoolTransactions
    {
        $transactions = array_filter(
            $this->transactions,
            fn (Section104PoolTransaction $transaction) => $transaction->date->isBefore($date)
                && (is_null($type) ? true : $transaction instanceof $type)
        );

        return new self($transactions);
    }

    public function acquisitionsMadeBefore(LocalDate $date): Section104PoolTransactions
    {
        return $this->transactionsMadeBefore($date, Section104PoolAcquisition::class);
    }

    public function disposalsMadeBefore(LocalDate $date): Section104PoolTransactions
    {
        return $this->transactionsMadeBefore($date, Section104PoolDisposal::class);
    }
}
