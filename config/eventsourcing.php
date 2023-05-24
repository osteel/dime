<?php

declare(strict_types=1);

use App\Services\ObjectHydration\Hydrators\CapitalGainHydrator;
use App\Services\ObjectHydration\Hydrators\FiatAmountHydrator;
use App\Services\ObjectHydration\Hydrators\LocalDateHydrator;
use App\Services\ObjectHydration\Hydrators\SharePoolingAssetAcquisitionHydrator;
use App\Services\ObjectHydration\Hydrators\SharePoolingAssetDisposalHydrator;
use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetAcquired;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetCostBasisIncreased;
use Domain\Aggregates\NonFungibleAsset\Events\NonFungibleAssetDisposedOf;
use Domain\Aggregates\NonFungibleAsset\NonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\ValueObjects\NonFungibleAssetId;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetAcquired;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposalReverted;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetDisposedOf;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetFiatCurrencySet;
use Domain\Aggregates\SharePoolingAsset\Events\SharePoolingAssetSet;
use Domain\Aggregates\SharePoolingAsset\SharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetId;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdated;
use Domain\Aggregates\TaxYear\Events\CapitalGainUpdateReverted;
use Domain\Aggregates\TaxYear\Events\IncomeUpdated;
use Domain\Aggregates\TaxYear\Events\NonAttributableAllowableCostUpdated;
use Domain\Aggregates\TaxYear\TaxYear;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\ValueObjects\FiatAmount;

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
        SharePoolingAssetFiatCurrencySet::class => 'share_pooling_asset.share_pooling_asset_fiat_currency_set',
        SharePoolingAssetSet::class => 'share_pooling_asset.share_pooling_asset_set',
        TaxYear::class => 'tax_year.tax_year',
        TaxYearId::class => 'tax_year.tax_year_id',
        CapitalGainUpdated::class => 'tax_year.capital_gain_updated',
        CapitalGainUpdateReverted::class => 'tax_year.capital_gain_update_reverted',
        IncomeUpdated::class => 'tax_year.income_updated',
        NonAttributableAllowableCostUpdated::class => 'tax_year.non_attributable_allowable_cost_updated',
    ],

    'hydrator_class_map' => [
        CapitalGain::class => CapitalGainHydrator::class,
        FiatAmount::class => FiatAmountHydrator::class,
        LocalDate::class => LocalDateHydrator::class,
        SharePoolingAssetAcquisition::class => SharePoolingAssetAcquisitionHydrator::class,
        SharePoolingAssetDisposal::class => SharePoolingAssetDisposalHydrator::class,
    ],
];
