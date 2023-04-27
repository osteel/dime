<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Repositories\SharePoolingAssetRepository;
use Domain\Aggregates\SharePoolingAsset\SharePoolingAsset;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('can acquire a share pooling asset', function () {
    $sharePoolingAsset = Mockery::spy(SharePoolingAsset::class);
    $sharePoolingAssetRepository = Mockery::mock(SharePoolingAssetRepository::class);

    $acquireSharePoolingAsset = new AcquireSharePoolingAsset(
        asset: new Asset('FOO'),
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('1'),
        costBasis: FiatAmount::GBP('1'),
    );

    $sharePoolingAssetRepository->shouldReceive('get')->once()->andReturn($sharePoolingAsset);
    $sharePoolingAssetRepository->shouldReceive('save')->once()->with($sharePoolingAsset);

    $acquireSharePoolingAsset->handle($sharePoolingAssetRepository);

    $sharePoolingAsset->shouldHaveReceived('acquire')->once()->with($acquireSharePoolingAsset);
});
