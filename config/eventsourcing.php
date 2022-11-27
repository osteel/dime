<?php

declare(strict_types=1);

use Domain\Nft\Events\NftAcquired;
use Domain\Nft\Events\NftCostBasisIncreased;
use Domain\Nft\Events\NftDisposedOf;
use Domain\Nft\Nft;
use Domain\Nft\NftId;
use Domain\SharePooling\Events\SharePoolingTokenAcquired;
use Domain\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\SharePooling\SharePooling;
use Domain\SharePooling\SharePoolingId;

return [
    'class_map' => [
        Nft::class => 'nft.nft',
        NftId::class => 'nft.nft_id',
        NftAcquired::class => 'nft.nft_acquired',
        NftCostBasisIncreased::class => 'nft.nft_cost_basis_increased',
        NftDisposedOf::class => 'nft.nft_disposed_of',
        SharePooling::class => 'share_pooling.share_pooling',
        SharePoolingId::class => 'share_pooling.share_pooling_id',
        SharePoolingTokenAcquired::class => 'share_pooling.share_pooling_acquired',
        SharePoolingTokenDisposalReverted::class => 'share_pooling.share_pooling_disposal_reverted',
        SharePoolingTokenDisposedOf::class => 'share_pooling.share_pooling_disposed_of',
    ],
];
