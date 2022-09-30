<?php

namespace Domain\Events;

use Domain\Aggregates\NftId;
use Domain\ValueObjects\FiatAmount;

final class NftCostBasisAveraged
{
    public function __construct(
        public readonly NftId $nftId,
        public readonly FiatAmount $averagingCostBasis,
    ) {
    }
}
