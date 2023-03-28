<?php

declare(strict_types=1);

namespace Domain\Aggregates\SharePoolingAsset\Actions;

use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetDisposal;

final readonly class RevertSharePoolingAssetDisposal
{
    public function __construct(
        public SharePoolingAssetDisposal $sharePoolingAssetDisposal,
    ) {
    }
}
