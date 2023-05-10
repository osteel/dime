<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\NonFungibleAsset\Actions\AcquireNonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Actions\IncreaseNonFungibleAssetCostBasis;
use Domain\Aggregates\NonFungibleAsset\NonFungibleAsset;
use Domain\Aggregates\NonFungibleAsset\Repositories\NonFungibleAssetRepository;
use Domain\Services\ActionRunner\ActionRunner;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;

it('can acquire a non-fungible asset', function () {
    $nonFungibleAsset = Mockery::spy(NonFungibleAsset::class);
    $nonFungibleAssetRepository = Mockery::mock(NonFungibleAssetRepository::class);
    $runner = Mockery::mock(ActionRunner::class);

    $acquireNonFungibleAsset = new AcquireNonFungibleAsset(
        asset: new Asset('FOO'),
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('1'),
    );

    $nonFungibleAssetRepository->shouldReceive('get')->once()->andReturn($nonFungibleAsset);
    $nonFungibleAssetRepository->shouldReceive('save')->once()->with($nonFungibleAsset);

    $acquireNonFungibleAsset->handle($nonFungibleAssetRepository, $runner);

    $nonFungibleAsset->shouldHaveReceived('acquire')->once()->with($acquireNonFungibleAsset);
});

it('can increase the cost basis of a non-fungible asset', function () {
    $nonFungibleAsset = Mockery::mock(NonFungibleAsset::class);
    $nonFungibleAssetRepository = Mockery::mock(NonFungibleAssetRepository::class);
    $runner = Mockery::mock(ActionRunner::class);

    $acquireNonFungibleAsset = new AcquireNonFungibleAsset(
        asset: new Asset('FOO'),
        date: LocalDate::parse('2015-10-21'),
        costBasis: FiatAmount::GBP('1'),
    );

    $nonFungibleAssetRepository->shouldReceive('get')->once()->andReturn($nonFungibleAsset);
    $nonFungibleAssetRepository->shouldNotReceive('save');

    $nonFungibleAsset->shouldReceive('isAlreadyAcquired')->once()->andReturn(true);
    $nonFungibleAsset->shouldNotReceive('acquire');

    $runner->shouldReceive('run')
        ->once()
        ->withArgs(fn (IncreaseNonFungibleAssetCostBasis $action) => $action->date === $acquireNonFungibleAsset->date
            && $action->costBasisIncrease === $acquireNonFungibleAsset->costBasis);

    $acquireNonFungibleAsset->handle($nonFungibleAssetRepository, $runner);
});