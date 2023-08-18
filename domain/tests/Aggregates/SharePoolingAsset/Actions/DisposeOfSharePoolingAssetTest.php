<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Repositories\SharePoolingAssetRepository;
use Domain\Aggregates\SharePoolingAsset\SharePoolingAssetContract;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('can dispose of a share pooling asset', function () {
    $sharePoolingAsset = Mockery::spy(SharePoolingAssetContract::class);
    $sharePoolingAssetRepository = Mockery::mock(SharePoolingAssetRepository::class);

    $disposeOfSharePoolingAsset = new DisposeOfSharePoolingAsset(
        asset: new Asset('FOO'),
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('1'),
        proceeds: FiatAmount::GBP('1'),
        forFiat: false,
    );

    $sharePoolingAssetRepository->shouldReceive('get')->once()->andReturn($sharePoolingAsset);
    $sharePoolingAssetRepository->shouldReceive('save')->once()->with($sharePoolingAsset);

    $disposeOfSharePoolingAsset($sharePoolingAssetRepository);

    $sharePoolingAsset->shouldHaveReceived('disposeOf')->once()->with($disposeOfSharePoolingAsset);
});
