<?php

namespace Domain\Section104Pool\ValueObjects;

use Brick\DateTime\LocalDate;
use Domain\Section104Pool\Enums\Section104PoolTransactionType;
use Domain\Tests\Section104Pool\Factories\ValueObjects\Section104PoolTransactionFactory;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class Section104PoolTransaction
{
    use HasFactory;

    public function __construct(
        public readonly LocalDate $date,
        public readonly Section104PoolTransactionType $type,
        public readonly string $quantity,
        public readonly FiatAmount $costBasis,
    ) {
    }

    protected static function newFactory(): Section104PoolTransactionFactory
    {
        return Section104PoolTransactionFactory::new();
    }
}
