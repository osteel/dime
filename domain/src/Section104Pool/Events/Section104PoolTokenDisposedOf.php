<?php

namespace Domain\Section104Pool\Events;

use Domain\Section104Pool\Section104PoolId;
use Domain\ValueObjects\FiatAmount;

final class Section104PoolTokenDisposedOf
{
    public function __construct(
        public readonly Section104PoolId $section104PoolId,
        public readonly string $quantity,
        public readonly FiatAmount $disposalProceeds,
    ) {
    }
}
