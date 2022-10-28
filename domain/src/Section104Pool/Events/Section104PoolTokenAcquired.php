<?php

namespace Domain\Section104Pool\Events;

use Brick\DateTime\LocalDate;
use Domain\Section104Pool\Section104PoolId;
use Domain\ValueObjects\FiatAmount;

final class Section104PoolTokenAcquired
{
    public function __construct(
        public readonly Section104PoolId $section104PoolId,
        public readonly LocalDate $date,
        public readonly string $quantity,
        public readonly FiatAmount $costBasis,
    ) {
    }
}