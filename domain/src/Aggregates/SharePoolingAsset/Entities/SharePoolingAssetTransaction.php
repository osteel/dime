<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Entities;

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stringable;

abstract class SharePoolingAssetTransaction implements Stringable
{
    use HasFactory;

    public readonly SharePoolingAssetTransactionId $id;

    public function __construct(
        public readonly LocalDate $date,
        public readonly Quantity $quantity,
        public readonly FiatAmount $costBasis,
        public readonly bool $processed = true,
        ?SharePoolingAssetTransactionId $id = null,
    ) {
        $this->id = $id ?? SharePoolingAssetTransactionId::generate();
    }

    abstract public function sameDayQuantity(): Quantity;

    abstract public function thirtyDayQuantity(): Quantity;

    public function section104PoolQuantity(): Quantity
    {
        return $this->quantity->minus($this->sameDayQuantity()->plus($this->thirtyDayQuantity()));
    }

    public function hasAvailableSameDayQuantity(): bool
    {
        return $this->availableSameDayQuantity()->isGreaterThan('0');
    }

    public function availableSameDayQuantity(): Quantity
    {
        return $this->quantity->minus($this->sameDayQuantity());
    }

    public function hasAvailableThirtyDayQuantity(): bool
    {
        return $this->availableThirtyDayQuantity()->isGreaterThan('0');
    }

    public function availableThirtyDayQuantity(): Quantity
    {
        // Same-day quantity always gets priority, and it is assumed that the existing
        // 30-day quantity has already been matched with priority transactions. That
        // leaves us with the section 104 pool quantity, which is what we return
        return $this->section104PoolQuantity();
    }
}
