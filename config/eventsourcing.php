<?php

declare(strict_types=1);

use Domain\Aggregates\Nft\Events\NftAcquired;
use Domain\Aggregates\Nft\Events\NftCostBasisIncreased;
use Domain\Aggregates\Nft\Events\NftDisposedOf;
use Domain\Aggregates\Nft\Nft;
use Domain\Aggregates\Nft\NftId;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenAcquired;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposalReverted;
use Domain\Aggregates\SharePooling\Events\SharePoolingTokenDisposedOf;
use Domain\Aggregates\SharePooling\SharePooling;
use Domain\Aggregates\SharePooling\SharePoolingId;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdated;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdateReverted;
use Domain\Aggregates\TaxYear\Events\IncomeUpdated;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostUpdated;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Aggregates\TaxYear\TaxYearId;

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
        TaxYear::class => 'tax_year.tax_year',
        TaxYearId::class => 'tax_year.tax_year_id',
        CapitalGainUpdated::class => 'tax_year.capital_gain_updated',
        CapitalGainUpdateReverted::class => 'tax_year.capital_gain_update_reverted',
        IncomeUpdated::class => 'tax_year.income_updated',
        NonAttributableAllowableCostUpdated::class => 'tax_year.non_attributable_allowable_cost_updated',
    ],
];
