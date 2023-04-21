<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Repositories;

use Domain\Aggregates\NonFungibleAsset\NonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;

interface NonFungibleAssetRepository
{
    public function get(NonFungibleAssetId $nonFungibleAssetId): NonFungibleAsset;

    public function save(NonFungibleAsset $nonFungibleAsset): void;
}
