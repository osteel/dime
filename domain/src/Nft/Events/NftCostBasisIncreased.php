<?php

namespace Domain\Nft\Events;

use Domain\Nft\NftId;
use Domain\ValueObjects\FiatAmount;

final class NftCostBasisIncreased
{
    public function __construct(
        public readonly NftId $nftId,
        public readonly FiatAmount $costBasisIncrease,
    ) {
    }
}
