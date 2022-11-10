<?php

namespace Domain\SharePooling\ValueObjects;

use ArrayIterator;
use Domain\SharePooling\ValueObjects\Exceptions\SharePoolingTransactionException;
use Domain\ValueObjects\Quantity;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int, SharePoolingTokenDisposal> */
final class SharePoolingTokenDisposals implements IteratorAggregate
{
    /** @param array<int, SharePoolingTokenDisposal> $transactions */
    private function __construct(private array $transactions = [])
    {
    }

    public static function make(SharePoolingTokenDisposal ...$transactions): self
    {
        return new self(array_values($transactions));
    }

    /** @return Traversable<int, SharePoolingTokenDisposal> */
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

    public function add(SharePoolingTokenDisposal ...$transactions): self
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
            fn (Quantity $total, SharePoolingTokenDisposal $transaction) => $total->plus($transaction->quantity),
            Quantity::zero()
        );
    }

    public function sameDayQuantity(): Quantity
    {
        return array_reduce(
            $this->transactions,
            fn (Quantity $total, SharePoolingTokenDisposal $transaction) => $total->plus($transaction->sameDayQuantity),
            Quantity::zero()
        );
    }

    public function withAvailableSameDayQuantity(): SharePoolingTokenDisposals
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTokenDisposal $transaction) => $transaction->hasAvailableSameDayQuantity(),
        );

        return self::make(...$transactions);
    }

    public function with30DayQuantity(): SharePoolingTokenDisposals
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTokenDisposal $transaction) => $transaction->has30DayQuantity(),
        );

        return self::make(...$transactions);
    }

    public function withSection104PoolQuantity(): SharePoolingTokenDisposals
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingTokenDisposal $transaction) => $transaction->hasSection104PoolQuantity(),
        );

        return self::make(...$transactions);
    }
}
