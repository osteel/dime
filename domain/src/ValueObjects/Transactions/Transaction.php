<?php

declare(strict_types=1);

namespace Domain\ValueObjects\Transactions;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\Fee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stringable;

abstract readonly class Transaction implements Stringable
{
    use HasFactory;

    public function __construct(
        public LocalDate $date,
        public ?Fee $fee = null,
    ) {
    }

    public function hasFee(): bool
    {
        return (bool) $this->fee?->marketValue->isGreaterThan('0');
    }

    public function feeIsFiat(): bool
    {
        return (bool) $this->fee?->isFiat();
    }

    abstract public function hasNonFungibleAsset(): bool;

    abstract public function hasSharePoolingAsset(): bool;
}
