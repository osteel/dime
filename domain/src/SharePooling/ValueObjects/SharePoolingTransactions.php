<?php

namespace Domain\SharePooling\ValueObjects;

use ArrayIterator;
use Brick\DateTime\LocalDate;
use Domain\SharePooling\ValueObjects\Exceptions\SharePoolingTransactionException;
use Domain\ValueObjects\Quantity;
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

    private function collectionClassForType(?string $type = null): string
    {
        return $type
            ? ($type === SharePoolingTokenAcquisition::class ? SharePoolingTokenAcquisitions::class : SharePoolingTokenDisposals::class)
            : SharePoolingTransactions::class;
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

    public function add(SharePoolingTransaction ...$transactions): self
    {
        foreach ($transactions as $transaction) {
            try {
                $transaction->setPosition($this->count());
            } catch (SharePoolingTransactionException) {
            }

            $this->transactions[$transaction->getPosition()] = $transaction;
        }

        return $this;
    }

    public function quantity(): Quantity
    {
        return array_reduce(
            $this->transactions,
            fn (Quantity $total, SharePoolingTransaction $transaction) => $transaction instanceof SharePoolingTokenAcquisition
                ? $total->plus($transaction->quantity)
                : $total->minus($transaction->quantity),
            new Quantity('0'),
        );
    }

    public function section104PoolQuantity(): Quantity
    {
        return array_reduce(
            $this->transactions,
            fn (Quantity $total, SharePoolingTransaction $transaction) => $transaction instanceof SharePoolingTokenAcquisition
                ? $total->plus($transaction->quantity)
                : $total->minus($transaction->quantity),
            new Quantity('0'),
        );
    }

    public function transactionsMadeOn(
        LocalDate $date,
        ?string $type = null
    ): SharePoolingTransactions|SharePoolingTokenAcquisitions|SharePoolingTokenDisposals {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTransaction $transaction) => $transaction->date->isEqualTo($date)
                && (is_null($type) ? true : $transaction instanceof $type)
        );

        return ($this->collectionClassForType($type))::make(...$transactions);
    }

    public function acquisitionsMadeOn(LocalDate $date): SharePoolingTokenAcquisitions
    {
        return $this->transactionsMadeOn($date, SharePoolingTokenAcquisition::class);
    }

    public function disposalsMadeOn(LocalDate $date): SharePoolingTokenDisposals
    {
        return $this->transactionsMadeOn($date, SharePoolingTokenDisposal::class);
    }

    public function transactionsMadeBetween(
        LocalDate $from,
        LocalDate $to,
        ?string $type = null,
    ): SharePoolingTransactions|SharePoolingTokenAcquisitions|SharePoolingTokenDisposals {
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

        return ($this->collectionClassForType($type))::make(...$transactions);
    }

    public function acquisitionsMadeBetween(LocalDate $from, LocalDate $to): SharePoolingTokenAcquisitions
    {
        return $this->transactionsMadeBetween($from, $to, SharePoolingTokenAcquisition::class);
    }

    public function disposalsMadeBetween(LocalDate $from, LocalDate $to): SharePoolingTokenDisposals
    {
        return $this->transactionsMadeBetween($from, $to, SharePoolingTokenDisposal::class);
    }

    public function transactionsMadeBefore(
        LocalDate $date,
        ?string $type = null
    ): SharePoolingTransactions|SharePoolingTokenAcquisitions|SharePoolingTokenDisposals {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTransaction $transaction) => $transaction->date->isBefore($date)
                && (is_null($type) ? true : $transaction instanceof $type)
        );

        return ($this->collectionClassForType($type))::make(...$transactions);
    }

    public function acquisitionsMadeBefore(LocalDate $date): SharePoolingTokenAcquisitions
    {
        return $this->transactionsMadeBefore($date, SharePoolingTokenAcquisition::class);
    }

    public function disposalsMadeBefore(LocalDate $date): SharePoolingTokenDisposals
    {
        return $this->transactionsMadeBefore($date, SharePoolingTokenDisposal::class);
    }
}
