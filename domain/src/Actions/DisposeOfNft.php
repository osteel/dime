<?php

namespace Domain\Actions;

use Domain\Aggregates\NftId;
use Domain\ValueObjects\FiatAmount;

final class DisposeOfNft
{
    public function __construct(
        public readonly NftId $nftId,
        public readonly FiatAmount $disposalProceeds,
    ) {
    }
}
