<?php

declare(strict_types=1);

use Domain\Nft\Events\NftAcquired;
use Domain\Nft\Events\NftCostBasisIncreased;
use Domain\Nft\Events\NftDisposedOf;
use Domain\Nft\Nft;
use Domain\Nft\NftId;

return [
    'class_map' => [
        Nft::class => 'nft.nft',
        NftId::class => 'nft.nft_id',
        NftAcquired::class => 'nft.nft_acquired',
        NftCostBasisIncreased::class => 'nft.nft_cost_basis_increased',
        NftDisposedOf::class => 'nft.nft_disposed_of',
    ],
];
