<?php

namespace Domain\Section104Pool\Actions;

use Domain\Section104Pool\Section104PoolId;
use Domain\ValueObjects\FiatAmount;

final class AcquireSection104PoolToken
{
    public function __construct(
        public readonly Section104PoolId $section104PoolId,
        public readonly string $quantity,
        public readonly FiatAmount $costBasis,
    ) {
    }
}
