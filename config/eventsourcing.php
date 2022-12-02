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
use Domain\TaxYear\Events\CapitalGainRecorded;
use Domain\TaxYear\Events\CapitalGainReverted;
use Domain\TaxYear\Events\CapitalLossRecorded;
use Domain\TaxYear\Events\CapitalLossReverted;
use Domain\TaxYear\Events\IncomeRecorded;
use Domain\TaxYear\Events\NonAttributableAllowableCostRecorded;
use Domain\TaxYear\TaxYear;
use Domain\TaxYear\TaxYearId;

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
        CapitalGainRecorded::class => 'tax_year.capital_gain_recorded',
        CapitalGainReverted::class => 'tax_year.capital_gain_reverted',
        CapitalLossRecorded::class => 'tax_year.capital_loss_recorded',
        CapitalLossReverted::class => 'tax_year.capital_loss_reverted',
        IncomeRecorded::class => 'tax_year.income_recorded',
        NonAttributableAllowableCostRecorded::class => 'tax_year.non_attributable_allowable_cost_recorded',
    ],
];
