<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Entities;

use ArrayIterator;
use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\ValueObjects\Quantity;
use IteratorAggregate;
use Traversable;

/** @implements IteratorAggregate<int,SharePoolingAssetTransaction> */
final class SharePoolingAssetTransactions implements IteratorAggregate
{
    /** @param array<int,SharePoolingAssetTransaction> $transactions */
    private function __construct(private array $transactions = [])
    {
    }

    public static function make(SharePoolingAssetTransaction ...$transactions): self
    {
        return new self(array_values($transactions));
    }

    /** @return Traversable<int, SharePoolingAssetTransaction> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->transactions);
    }

    private function getIndexForId(SharePoolingAssetTransactionId $id): ?int
    {
        $index = array_search(
            $id,
            array_map(fn (SharePoolingAssetTransaction $transaction) => $transaction->id, $this->transactions),
        );

        return $index !== false ? $index : null;
    }

    private function collectionClassForType(?string $type = null): string
    {
        return match ($type) {
            SharePoolingAssetAcquisition::class => SharePoolingAssetAcquisitions::class,
            SharePoolingAssetDisposal::class => SharePoolingAssetDisposals::class,
            default => SharePoolingAssetTransactions::class,
        };
    }

    public function isEmpty(): bool
    {
        return empty($this->transactions);
    }

    public function count(): int
    {
        return count($this->transactions);
    }

    public function first(): ?SharePoolingAssetTransaction
    {
        return $this->transactions[0] ?? null;
    }

    public function get(int $index): ?SharePoolingAssetTransaction
    {
        return $this->transactions[$index] ?? null;
    }

    public function getForId(SharePoolingAssetTransactionId $id): ?SharePoolingAssetTransaction
    {
        $index = $this->getIndexForId($id);

        return is_null($index) ? null : $this->get($index);
    }

    public function add(SharePoolingAssetTransaction ...$transactions): self
    {
        foreach ($transactions as $transaction) {
            $index = $this->getIndexForId($transaction->id) ?? $this->count();

            $this->transactions[$index] = $transaction;
        }

        return $this;
    }

    public function processed(): SharePoolingAssetTransactions
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingAssetTransaction $transaction) => $transaction->processed,
        );

        return self::make(...$transactions);
    }

    public function quantity(): Quantity
    {
        return array_reduce(
            $this->transactions,
            fn (Quantity $total, SharePoolingAssetTransaction $transaction) => $transaction instanceof SharePoolingAssetAcquisition
                ? $total->plus($transaction->quantity)
                : $total->minus($transaction->quantity),
            Quantity::zero(),
        );
    }

    public function madeOn(
        LocalDate $date,
        ?string $type = null
    ): SharePoolingAssetTransactions|SharePoolingAssetAcquisitions|SharePoolingAssetDisposals {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingAssetTransaction $transaction) => $transaction->date->isEqualTo($date)
                && (is_null($type) ? true : $transaction instanceof $type),
        );

        return ($this->collectionClassForType($type))::make(...$transactions);
    }

    public function acquisitionsMadeOn(LocalDate $date): SharePoolingAssetAcquisitions
    {
        // @phpstan-ignore-next-line
        return $this->madeOn($date, SharePoolingAssetAcquisition::class);
    }

    public function disposalsMadeOn(LocalDate $date): SharePoolingAssetDisposals
    {
        // @phpstan-ignore-next-line
        return $this->madeOn($date, SharePoolingAssetDisposal::class);
    }

    /** Return transactions made between two dates (inclusive) */
    public function madeBetween(
        LocalDate $from,
        LocalDate $to,
        ?string $type = null,
    ): SharePoolingAssetTransactions|SharePoolingAssetAcquisitions|SharePoolingAssetDisposals {
        if ($from->isEqualTo($to)) {
            return $this->madeOn($from, $type);
        }

        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingAssetTransaction $transaction) =>
                $transaction->date->isAfterOrEqualTo($from->isBefore($to) ? $from : $to)
                && $transaction->date->isBeforeOrEqualTo($to->isAfter($from) ? $to : $from)
                && (is_null($type) ? true : $transaction instanceof $type),
        );

        return ($this->collectionClassForType($type))::make(...$transactions);
    }

    /** Return acquisitions made between two dates (inclusive) */
    public function acquisitionsMadeBetween(LocalDate $from, LocalDate $to): SharePoolingAssetAcquisitions
    {
        // @phpstan-ignore-next-line
        return $this->madeBetween($from, $to, SharePoolingAssetAcquisition::class);
    }

    /** Return disposals made between two dates (inclusive) */
    public function disposalsMadeBetween(LocalDate $from, LocalDate $to): SharePoolingAssetDisposals
    {
        // @phpstan-ignore-next-line
        return $this->madeBetween($from, $to, SharePoolingAssetDisposal::class);
    }

    /** Return transactions made before a date (exclusive) */
    public function madeBefore(
        LocalDate $date,
        ?string $type = null
    ): SharePoolingAssetTransactions|SharePoolingAssetAcquisitions|SharePoolingAssetDisposals {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingAssetTransaction $transaction) => $transaction->date->isBefore($date)
                && (is_null($type) ? true : $transaction instanceof $type),
        );

        return ($this->collectionClassForType($type))::make(...$transactions);
    }

    /** Return transactions made before a date (inclusive) */
    public function madeBeforeOrOn(
        LocalDate $date,
        ?string $type = null
    ): SharePoolingAssetTransactions|SharePoolingAssetAcquisitions|SharePoolingAssetDisposals {
        return $this->madeBefore($date->plusDays(1), $type);
    }

    /** Return acquisitions made before a date (exclusive) */
    public function acquisitionsMadeBefore(LocalDate $date): SharePoolingAssetAcquisitions
    {
        // @phpstan-ignore-next-line
        return $this->madeBefore($date, SharePoolingAssetAcquisition::class);
    }

    /** Return acquisitions made before a date (inclusive) */
    public function acquisitionsMadeBeforeOrOn(LocalDate $date): SharePoolingAssetAcquisitions
    {
        return $this->acquisitionsMadeBefore($date->plusDays(1));
    }

    /** Return disposals made before a date (exclusive) */
    public function disposalsMadeBefore(LocalDate $date): SharePoolingAssetDisposals
    {
        // @phpstan-ignore-next-line
        return $this->madeBefore($date, SharePoolingAssetDisposal::class);
    }

    /** Return disposals made before a date (inclusive) */
    public function disposalsMadeBeforeOrOn(LocalDate $date): SharePoolingAssetDisposals
    {
        return $this->disposalsMadeBefore($date->plusDays(1));
    }

    /** Return transactions made after a date (exclusive) */
    public function madeAfter(
        LocalDate $date,
        ?string $type = null
    ): SharePoolingAssetTransactions|SharePoolingAssetAcquisitions|SharePoolingAssetDisposals {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingAssetTransaction $transaction) => $transaction->date->isAfter($date)
                && (is_null($type) ? true : $transaction instanceof $type),
        );

        return ($this->collectionClassForType($type))::make(...$transactions);
    }

    /** Return transactions made after a date (inclusive) */
    public function madeAfterOrOn(
        LocalDate $date,
        ?string $type = null
    ): SharePoolingAssetTransactions|SharePoolingAssetAcquisitions|SharePoolingAssetDisposals {
        return $this->madeAfter($date->minusDays(1), $type);
    }

    /** Return acquisitions made after a date (exclusive) */
    public function acquisitionsMadeAfter(LocalDate $date): SharePoolingAssetAcquisitions
    {
        // @phpstan-ignore-next-line
        return $this->madeAfter($date, SharePoolingAssetAcquisition::class);
    }

    /** Return acquisitions made after a date (inclusive) */
    public function acquisitionsMadeAfterOrOn(LocalDate $date): SharePoolingAssetAcquisitions
    {
        return $this->acquisitionsMadeAfter($date->minusDays(1));
    }

    /** Return disposals made after a date (exclusive) */
    public function disposalsMadeAfter(LocalDate $date): SharePoolingAssetDisposals
    {
        // @phpstan-ignore-next-line
        return $this->madeAfter($date, SharePoolingAssetDisposal::class);
    }

    /** Return disposals made after a date (inclusive) */
    public function disposalsMadeAfterOrOn(LocalDate $date): SharePoolingAssetDisposals
    {
        return $this->disposalsMadeAfter($date->minusDays(1));
    }

    public function disposalsWithThirtyDayQuantityMatchedWith(SharePoolingAssetAcquisition $acquisition): SharePoolingAssetDisposals
    {
        $transactions = array_filter(
            $this->transactions,
            fn (SharePoolingAssetTransaction $transaction) => $transaction instanceof SharePoolingAssetDisposal
                && $transaction->hasThirtyDayQuantityMatchedWith($acquisition),
        );

        return SharePoolingAssetDisposals::make(...$transactions);
    }
}
