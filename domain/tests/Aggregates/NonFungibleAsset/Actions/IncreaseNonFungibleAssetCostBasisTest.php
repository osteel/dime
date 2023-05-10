<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Actions\IncreaseNonFungibleAssetCostBasis;
use Domain\Aggregates\NonFungibleAsset\NonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetRepository;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;

it('can increase the cost basis of a non-fungible asset', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAsset::class);
    $nonFungibleAssetRepository = Mockery::mock(NonFungibleAssetRepository::class);

    $increaseNonFungibleAssetCostBasis = new IncreaseNonFungibleAssetCostBasis(
        asset: new Asset('FOO'),
        date: LocalDate::parse('2015-10-21'),
        costBasisIncrease: FiatAmount::GBP('1'),
    );

    $nonFungibleAssetRepository->shouldReceive('get')->once()->andReturn($nonFungibleAsset);
    $nonFungibleAssetRepository->shouldReceive('save')->once()->with($nonFungibleAsset);

    $increaseNonFungibleAssetCostBasis->handle($nonFungibleAssetRepository);

    $nonFungibleAsset->shouldHaveReceived('increaseCostBasis')->once()->with($increaseNonFungibleAssetCostBasis);
});