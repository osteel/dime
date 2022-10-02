<?php

namespace Domain\Events;

use Domain\Aggregates\NftId;
use Domain\ValueObjects\FiatAmount;

final class NftDisposedOf
{
    public function __construct(
        public readonly NftId $nftId,
        public readonly FiatAmount $costBasis,
        public readonly FiatAmount $disposalProceeds,
    ) {
    }
}
