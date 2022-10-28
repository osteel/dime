<?php

namespace Domain\Section104Pool\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Tests\Section104Pool\Factories\ValueObjects\Section104PoolDisposalFactory;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class Section104PoolDisposal extends Section104PoolTransaction
{
    use HasFactory;

    public function __construct(
        public readonly LocalDate $date,
        public readonly string $quantity,
        public readonly FiatAmount $costBasis,
        public readonly FiatAmount $disposalProceeds,
    ) {
    }

    protected static function newFactory(): Section104PoolDisposalFactory
    {
        return Section104PoolDisposalFactory::new();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s: disposed of %s tokens for %s (cost basis: %s)',
            $this->date,
            $this->quantity,
            $this->disposalProceeds,
            $this->costBasis,
        );
    }
}
