<?php

namespace Domain\Section104Pool\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Section104Pool\Enums\Section104PoolTransactionType;
use Domain\Services\Math\Math;
use Domain\ValueObjects\FiatAmount;

final class Section104PoolTransactions
{
    /** @param array<int, Section104PoolTransaction> $transactions */
    private function __construct(private array $transactions = [])
    {
    }

    public static function make(Section104PoolTransaction ...$transactions): self
    {
        return new self(array_values($transactions));
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

    /**
     * Return a new class instance.
     */
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

    public function averageCostBasisPerUnit(): ?FiatAmount
    {
        if (empty($this->transactions)) {
            return null;
        }

        $transactions = $this->transactions;
        $first = array_shift($transactions);

        $costBasis = array_reduce(
            $transactions,
            fn (FiatAmount $total, Section104PoolTransaction $transaction) => $total->plus($transaction->costBasis),
            $first->costBasis,
        );

        return $costBasis->dividedBy($this->quantity());
    }

    public function transactionsMadeOn(
        LocalDate $date,
        ?Section104PoolTransactionType $type = null,
    ): Section104PoolTransactions {
        $transactions = array_filter(
            $this->transactions,
            fn (Section104PoolTransaction $transaction) => $transaction->date->isEqualTo($date)
                && (is_null($type) ? true : $transaction->type === $type)
        );

        return new self($transactions);
    }

    public function acquisitionsMadeOn(LocalDate $date): Section104PoolTransactions
    {
        return $this->transactionsMadeOn($date, Section104PoolTransactionType::Acquisition);
    }

    public function disposalsMadeOn(LocalDate $date): Section104PoolTransactions
    {
        return $this->transactionsMadeOn($date, Section104PoolTransactionType::Disposal);
    }
}
