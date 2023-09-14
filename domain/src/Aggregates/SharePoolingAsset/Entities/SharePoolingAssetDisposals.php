<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Entities;

use ArrayIterator;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\ValueObjects\Quantity;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int,SharePoolingAssetDisposal> */
final class SharePoolingAssetDisposals implements IteratorAggregate
{
    /** @param array<int,SharePoolingAssetDisposal> $disposals */
    private function __construct(private array $disposals = [])
    {
    }

    public static function make(SharePoolingAssetDisposal ...$disposals): self
    {
        return new self(array_values($disposals));
    }

    /** @return Traversable<int, SharePoolingAssetDisposal> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->disposals);
    }

    private function getIndexForId(SharePoolingAssetTransactionId $id): ?int
    {
        $index = array_search(
            $id,
            array_map(fn (SharePoolingAssetTransaction $disposal) => $disposal->id, $this->disposals),
        );

        return $index !== false ? $index : null;
    }

    public function isEmpty(): bool
    {
        return empty($this->disposals);
    }

    public function count(): int
    {
        return count($this->disposals);
    }

    public function add(SharePoolingAssetDisposal ...$disposals): self
    {
        foreach ($disposals as $disposal) {
            $index = $this->getIndexForId($disposal->id) ?? $this->count();

            $this->disposals[$index] = $disposal;
        }

        return $this;
    }

    public function reverse(): SharePoolingAssetDisposals
    {
        return new self(array_reverse($this->disposals));
    }

    public function withAvailableSameDayQuantity(): SharePoolingAssetDisposals
    {
        $disposals = array_filter(
            $this->disposals,
            fn (SharePoolingAssetDisposal $disposal) => $disposal->hasAvailableSameDayQuantity(),
        );

        return self::make(...$disposals);
    }

    public function availableSameDayQuantity(): Quantity
    {
        return array_reduce(
            $this->disposals,
            fn (Quantity $total, SharePoolingAssetDisposal $disposal) => $total->plus($disposal->availableSameDayQuantity()),
            Quantity::zero(),
        );
    }

    public function withAvailableThirtyDayQuantity(): SharePoolingAssetDisposals
    {
        $disposals = array_filter(
            $this->disposals,
            fn (SharePoolingAssetDisposal $disposal) => $disposal->hasAvailableThirtyDayQuantity(),
        );

        return self::make(...$disposals);
    }
}
