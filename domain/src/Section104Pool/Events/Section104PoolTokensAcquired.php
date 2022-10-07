<?php

namespace Domain\Section104Pool\Events;

use Domain\Section104Pool\Section104PoolId;
use Domain\ValueObjects\FiatAmount;

final class Section104PoolTokensAcquired
{
    public function __construct(
        public readonly Section104PoolId $section104PoolId,
        public readonly string $previousQuantity,
        public readonly string $extraQuantity,
        public readonly string $newQuantity,
        public readonly FiatAmount $previousCostBasis,
        public readonly FiatAmount $extraCostBasis,
        public readonly FiatAmount $newCostBasis,
        public readonly FiatAmount $previousAverageCostBasisPerUnit,
        public readonly FiatAmount $newAverageCostBasisPerUnit,
    ) {
    }
}
