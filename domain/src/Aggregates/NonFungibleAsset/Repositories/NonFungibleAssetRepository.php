<?php

declare(strict_types=1);

namespace Domain\Aggregates\NonFungibleAsset\Repositories;

use Domain\Aggregates\NonFungibleAsset\NonFungibleAssetContract;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;

interface NonFungibleAssetRepository
{
    public function get(NonFungibleAssetId $nonFungibleAssetId): NonFungibleAssetContract;

    public function save(NonFungibleAssetContract $nonFungibleAsset): void;
}
