<?php

declare(strict_types=1);

namespace Domain\Nft\Repositories;

use Domain\Nft\Nft;
use Domain\Nft\NftId;

interface NftRepository
{
    public function get(NftId $nftId): Nft;
}
