<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Events;

use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;

final readonly class SharePoolingAssetDisposedOf
{
    public function __construct(
        public SharePoolingAssetDisposal $disposal,
    ) {
    }
}
