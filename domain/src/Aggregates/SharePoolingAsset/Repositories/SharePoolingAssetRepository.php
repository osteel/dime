<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Repositories;

use Domain\Aggregates\SharePoolingAsset\SharePoolingAssetContract;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;

interface SharePoolingAssetRepository
{
    public function get(SharePoolingAssetId $sharePoolingAssetId): SharePoolingAssetContract;

    public function save(SharePoolingAssetContract $sharePoolingAsset): void;
}
