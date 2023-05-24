<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Events;

use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;

final readonly class SharePoolingAssetAcquired
{
    public function __construct(
        public SharePoolingAssetAcquisition $acquisition,
    ) {
    }
}
