<?php

declare(strict_types=1);

namespace Domain\Nft\Actions;

use Domain\Nft\NftId;
use Domain\ValueObjects\FiatAmount;

final class DisposeOfNft
{
    public function __construct(
        public readonly NftId $nftId,
        public readonly FiatAmount $proceeds,
    ) {
    }
}
