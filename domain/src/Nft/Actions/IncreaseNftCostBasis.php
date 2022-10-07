<?php

namespace Domain\Nft\Actions;

use Domain\Nft\NftId;
use Domain\ValueObjects\FiatAmount;

final class IncreaseNftCostBasis
{
    public function __construct(
        public readonly NftId $nftId,
        public readonly FiatAmount $extraCostBasis,
    ) {
    }
}
