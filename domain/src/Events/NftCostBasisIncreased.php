<?php

namespace Domain\Events;

use Domain\Aggregates\NftId;
use Domain\ValueObjects\FiatAmount;

final class NftCostBasisIncreased
{
    public function __construct(
        public readonly NftId $nftId,
        public readonly FiatAmount $previousCostBasis,
        public readonly FiatAmount $extraCostBasis,
        public readonly FiatAmount $newCostBasis,
    ) {
    }
}
