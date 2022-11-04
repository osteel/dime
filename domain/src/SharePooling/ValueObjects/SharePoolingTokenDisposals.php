<?php

namespace Domain\SharePooling\ValueObjects;

use ArrayIterator;
use Domain\Services\Math\Math;
use Domain\SharePooling\ValueObjects\Exceptions\SharePoolingTransactionException;
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

    public function quantity(): string
    {
        return array_reduce(
            $this->transactions,
            fn (string $total, SharePoolingTokenDisposal $transaction) => Math::add($total, $transaction->quantity),
            '0'
        );
    }

    public function sameDayQuantity(): string
    {
        return array_reduce(
            $this->transactions,
            fn (string $total, SharePoolingTokenDisposal $transaction) => Math::add($total, $transaction->sameDayQuantity),
            '0'
        );
    }
}
