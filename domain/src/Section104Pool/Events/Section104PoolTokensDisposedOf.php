<?php

namespace Domain\Section104Pool\Events;

use Domain\Section104Pool\Section104PoolId;
use Domain\ValueObjects\FiatAmount;

final class Section104PoolTokensDisposedOf
{
    public function __construct(
        public readonly Section104PoolId $section104PoolId,
        public readonly string $previousQuantity,
        public readonly string $disposedOfQuantity,
        public readonly string $newQuantity,
        public readonly FiatAmount $previousCostBasis,
        public readonly FiatAmount $averageCostBasisPerUnit,
        public readonly FiatAmount $newCostBasis,
        public readonly FiatAmount $disposalProceeds,
    ) {
    }
}
