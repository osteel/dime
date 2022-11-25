<?php

declare(strict_types=1);

namespace Domain\Nft\Events;

use Domain\Nft\NftId;
use Domain\ValueObjects\FiatAmount;

final class NftAcquired
{
    public function __construct(
        public readonly NftId $nftId,
        public readonly FiatAmount $costBasis,
    ) {
    }
}
