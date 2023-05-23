<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Events;

use App\Services\ObjectHydrators\SharePoolingAssetDisposalHydrator;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;

final readonly class SharePoolingAssetDisposalReverted
{
    public function __construct(
        #[SharePoolingAssetDisposalHydrator]
        public SharePoolingAssetDisposal $disposal,
    ) {
    }
}
