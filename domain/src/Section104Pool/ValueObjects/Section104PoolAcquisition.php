<?php

namespace Domain\Section104Pool\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Tests\Section104Pool\Factories\ValueObjects\Section104PoolAcquisitionFactory;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class Section104PoolAcquisition extends Section104PoolTransaction
{
    use HasFactory;

    public function __construct(
        public readonly LocalDate $date,
        public readonly string $quantity,
        public readonly FiatAmount $costBasis,
        public readonly string $quantityMatchedForPooling,
    ) {
    }

    protected static function newFactory(): Section104PoolAcquisitionFactory
    {
        return Section104PoolAcquisitionFactory::new();
    }

    public function __toString(): string
    {
        return sprintf('%s: acquired %s tokens for %s', $this->date, $this->quantity, $this->costBasis);
    }
}
