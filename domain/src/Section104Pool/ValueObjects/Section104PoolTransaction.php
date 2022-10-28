<?php

namespace Domain\Section104Pool\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stringable;

abstract class Section104PoolTransaction implements Stringable
{
    use HasFactory;

    public function __construct(
        public readonly LocalDate $date,
        public readonly string $quantity,
        public readonly FiatAmount $costBasis,
    ) {
    }

    public function averageCostBasisPerUnit(): ?FiatAmount
    {
        return $this->costBasis()->dividedBy($this->quantity);
    }
}
