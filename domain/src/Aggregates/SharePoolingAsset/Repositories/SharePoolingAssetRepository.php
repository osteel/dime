<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Repositories;

use Domain\Aggregates\SharePoolingAsset\SharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\SharePoolingAssetId;

interface SharePoolingAssetRepository
{
    public function get(SharePoolingAssetId $sharePoolingAssetId): SharePoolingAsset;

    public function save(SharePoolingAsset $sharePoolingAsset): void;
}
