<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Actions\DisposeOfNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\NonFungibleAssetContract;
use Domain\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetRepository;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;

it('can dispose of a non-fungible asset', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAssetContract::class);
    $nonFungibleAssetRepository = Mockery::mock(NonFungibleAssetRepository::class);

    $disposeOfNonFungibleAsset = new DisposeOfNonFungibleAsset(
        asset: Asset::nonFungible('foo'),
        date: LocalDate::parse('2015-10-21'),
        proceeds: FiatAmount::GBP('1'),
    );

    $nonFungibleAssetRepository->shouldReceive('get')->once()->andReturn($nonFungibleAsset);
    $nonFungibleAssetRepository->shouldReceive('save')->once()->with($nonFungibleAsset);

    $disposeOfNonFungibleAsset->handle($nonFungibleAssetRepository);

    $nonFungibleAsset->shouldHaveReceived('disposeOf')->once()->with($disposeOfNonFungibleAsset);
});
