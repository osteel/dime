<?php

declare(strict_types=1);

use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetAcquired;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetCostBasisIncreased;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetDisposedOf;
use Domain\Aggregates\NonFungibleAsset\NonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\NonFungibleAssetId;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetAcquired;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposalReverted;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposedOf;
use Domain\Aggregates\SharePoolingAsset\SharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\SharePoolingAssetId;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdated;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdateReverted;
use Domain\Aggregates\TaxYear\Events\IncomeUpdated;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostUpdated;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Aggregates\TaxYear\TaxYearId;

return [
    'class_map' => [
        NonFungibleAsset::class => 'non_fungible_asset.non_fungible_asset',
        NonFungibleAssetId::class => 'non_fungible_asset.non_fungible_asset_id',
        NonFungibleAssetAcquired::class => 'non_fungible_asset.non_fungible_asset_acquired',
        NonFungibleAssetCostBasisIncreased::class => 'non_fungible_asset.non_fungible_asset_cost_basis_increased',
        NonFungibleAssetDisposedOf::class => 'non_fungible_asset.non_fungible_asset_disposed_of',
        SharePoolingAsset::class => 'share_pooling_asset.share_pooling_asset',
        SharePoolingAssetId::class => 'share_pooling_asset.share_pooling_asset_id',
        SharePoolingAssetAcquired::class => 'share_pooling_asset.share_pooling_asset_acquired',
        SharePoolingAssetDisposalReverted::class => 'share_pooling_asset.share_pooling_asset_disposal_reverted',
        SharePoolingAssetDisposedOf::class => 'share_pooling_asset.share_pooling_asset_disposed_of',
        TaxYear::class => 'tax_year.tax_year',
        TaxYearId::class => 'tax_year.tax_year_id',
        CapitalGainUpdated::class => 'tax_year.capital_gain_updated',
        CapitalGainUpdateReverted::class => 'tax_year.capital_gain_update_reverted',
        IncomeUpdated::class => 'tax_year.income_updated',
        NonAttributableAllowableCostUpdated::class => 'tax_year.non_attributable_allowable_cost_updated',
    ],
];
