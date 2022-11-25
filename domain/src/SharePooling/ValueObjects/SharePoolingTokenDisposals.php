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
    /** @param array<int, SharePoolingTokenDisposal> $disposals */
    private function __construct(private array $disposals = [])
    {
    }

    public static function make(SharePoolingTokenDisposal ...$disposals): self
    {
        return new self(array_values($disposals));
    }

    /** @return Traversable<int, SharePoolingTokenDisposal> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->disposals);
    }

    public function isEmpty(): bool
    {
        return empty($this->disposals);
    }

    public function count(): int
    {
        return count($this->disposals);
    }

    public function add(SharePoolingTokenDisposal ...$disposals): self
    {
        foreach ($disposals as $disposal) {
            try {
                $disposal->setPosition($this->count());
            } catch (SharePoolingTransactionException) {
            }

            assert(! is_null($position = $disposal->getPosition()));

            $this->disposals[$position] = $disposal;
        }

        return $this;
    }

    public function reverse(): SharePoolingTokenDisposals
    {
        return new self(array_reverse($this->disposals));
    }

    public function unprocessed(): SharePoolingTokenDisposals
    {
        $disposals = array_filter(
            $this->disposals,
            fn (SharePoolingTokenDisposal $disposal) => ! $disposal->isProcessed(),
        );

        return self::make(...$disposals);
    }

    public function withAvailableSameDayQuantity(): SharePoolingTokenDisposals
    {
        $disposals = array_filter(
            $this->disposals,
            fn (SharePoolingTokenDisposal $disposal) => $disposal->hasAvailableSameDayQuantity(),
        );

        return self::make(...$disposals);
    }

    public function availableSameDayQuantity(): Quantity
    {
        return array_reduce(
            $this->disposals,
            fn (Quantity $total, SharePoolingTokenDisposal $disposal) => $total->plus($disposal->availableSameDayQuantity()),
            Quantity::zero(),
        );
    }

    public function withAvailableThirtyDayQuantity(): SharePoolingTokenDisposals
    {
        $disposals = array_filter(
            $this->disposals,
            fn (SharePoolingTokenDisposal $disposal) => $disposal->hasAvailableThirtyDayQuantity(),
        );

        return self::make(...$disposals);
    }
}
