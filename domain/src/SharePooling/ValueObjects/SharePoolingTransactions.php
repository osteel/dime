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
        return new self(array_map(
            fn (SharePoolingTransaction $transation) => $transation->copy(),
            $this->transactions,
        ));
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

    public function get(int $position): ?SharePoolingTransaction
    {
        return $this->transactions[$position] ?? null;
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

    public function processed(): SharePoolingTransactions
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTransaction $transaction) => $transaction->isProcessed(),
        );

        return self::make(...$transactions);
    }

    public function quantity(): Quantity
    {
        return array_reduce(
            $this->transactions,
            fn (Quantity $total, SharePoolingTransaction $transaction) => $transaction instanceof SharePoolingTokenAcquisition
                ? $total->plus($transaction->quantity)
                : $total->minus($transaction->quantity),
            Quantity::zero(),
        );
    }

    public function madeOn(
        LocalDate $date,
        ?string $type = null
    ): SharePoolingTransactions|SharePoolingTokenAcquisitions|SharePoolingTokenDisposals {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTransaction $transaction) => $transaction->date->isEqualTo($date)
                && (is_null($type) ? true : $transaction instanceof $type),
        );

        return ($this->collectionClassForType($type))::make(...$transactions);
    }

    public function acquisitionsMadeOn(LocalDate $date): SharePoolingTokenAcquisitions
    {
        return $this->madeOn($date, SharePoolingTokenAcquisition::class);
    }

    public function disposalsMadeOn(LocalDate $date): SharePoolingTokenDisposals
    {
        return $this->madeOn($date, SharePoolingTokenDisposal::class);
    }

    public function madeBetween(
        LocalDate $from,
        LocalDate $to,
        ?string $type = null,
    ): SharePoolingTransactions|SharePoolingTokenAcquisitions|SharePoolingTokenDisposals {
        if ($from->isEqualTo($to)) {
            return $this->madeOn($from, $type);
        }

        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTransaction $transaction) =>
                $transaction->date->isAfterOrEqualTo($from->isBefore($to) ? $from : $to)
                && $transaction->date->isBeforeOrEqualTo($to->isAfter($from) ? $to : $from)
                && (is_null($type) ? true : $transaction instanceof $type),
        );

        return ($this->collectionClassForType($type))::make(...$transactions);
    }

    public function acquisitionsMadeBetween(LocalDate $from, LocalDate $to): SharePoolingTokenAcquisitions
    {
        return $this->madeBetween($from, $to, SharePoolingTokenAcquisition::class);
    }

    public function disposalsMadeBetween(LocalDate $from, LocalDate $to): SharePoolingTokenDisposals
    {
        return $this->madeBetween($from, $to, SharePoolingTokenDisposal::class);
    }

    public function madeBefore(
        LocalDate $date,
        ?string $type = null
    ): SharePoolingTransactions|SharePoolingTokenAcquisitions|SharePoolingTokenDisposals {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTransaction $transaction) => $transaction->date->isBefore($date)
                && (is_null($type) ? true : $transaction instanceof $type),
        );

        return ($this->collectionClassForType($type))::make(...$transactions);
    }

    public function madeBeforeOrOn(
        LocalDate $date,
        ?string $type = null
    ): SharePoolingTransactions|SharePoolingTokenAcquisitions|SharePoolingTokenDisposals {
        return $this->madeBefore($date->plusDays(1), $type);
    }

    public function acquisitionsMadeBefore(LocalDate $date): SharePoolingTokenAcquisitions
    {
        return $this->madeBefore($date, SharePoolingTokenAcquisition::class);
    }

    public function acquisitionsMadeBeforeOrOn(LocalDate $date): SharePoolingTokenAcquisitions
    {
        return $this->acquisitionsMadeBefore($date->plusDays(1));
    }

    public function disposalsMadeBefore(LocalDate $date): SharePoolingTokenDisposals
    {
        return $this->madeBefore($date, SharePoolingTokenDisposal::class);
    }

    public function disposalsMadeBeforeOrOn(LocalDate $date): SharePoolingTokenDisposals
    {
        return $this->disposalsMadeBefore($date->plusDays(1));
    }

    public function madeAfter(
        LocalDate $date,
        ?string $type = null
    ): SharePoolingTransactions|SharePoolingTokenAcquisitions|SharePoolingTokenDisposals {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTransaction $transaction) => $transaction->date->isAfter($date)
                && (is_null($type) ? true : $transaction instanceof $type),
        );

        return ($this->collectionClassForType($type))::make(...$transactions);
    }

    public function madeAfterOrOn(
        LocalDate $date,
        ?string $type = null
    ): SharePoolingTransactions|SharePoolingTokenAcquisitions|SharePoolingTokenDisposals {
        return $this->madeAfter($date->minusDays(1), $type);
    }

    public function acquisitionsMadeAfter(LocalDate $date): SharePoolingTokenAcquisitions
    {
        return $this->madeAfter($date, SharePoolingTokenAcquisition::class);
    }

    public function acquisitionsMadeAfterOrOn(LocalDate $date): SharePoolingTokenAcquisitions
    {
        return $this->acquisitionsMadeAfter($date->minusDays(1));
    }

    public function disposalsMadeAfter(LocalDate $date): SharePoolingTokenDisposals
    {
        return $this->madeAfter($date, SharePoolingTokenDisposal::class);
    }

    public function disposalsMadeAfterOrOn(LocalDate $date): SharePoolingTokenDisposals
    {
        return $this->disposalsMadeAfter($date->minusDays(1));
    }

    /** @throws \Domain\SharePooling\ValueObjects\Exceptions\QuantityBreakdownException */
    public function disposalsWithThirtyDayQuantityMatchedWith(SharePoolingTokenAcquisition $acquisition): SharePoolingTokenDisposals
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTransaction $transaction) => $transaction instanceof SharePoolingTokenDisposal
                && $transaction->hasThirtyDayQuantityMatchedWith($acquisition),
        );

        return SharePoolingTokenDisposals::make(...$transactions);
    }
}
