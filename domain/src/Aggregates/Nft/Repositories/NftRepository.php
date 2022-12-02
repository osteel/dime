<?php

declare(strict_types=1);

namespace Domain\Aggregates\Nft\Repositories;

use Domain\Aggregates\Nft\Nft;
use Domain\Aggregates\Nft\NftId;

interface NftRepository
{
    public function get(NftId $nftId): Nft;
}
