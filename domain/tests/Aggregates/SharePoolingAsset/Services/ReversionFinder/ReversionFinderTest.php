<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\AcquireSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetTransactions;
use Domain\Aggregates\SharePoolingAsset\Services\ReversionFinder\ReversionFinder;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('returns an empty collection of disposals when the acquired quantity is zero', function () {
    $acquisition = new AcquireSharePoolingAsset(
        asset: new Asset('FOO'),
        date: LocalDate::parse('2015-10-21'),
        quantity: Quantity::zero(),
        costBasis: FiatAmount::GBP('0'),
    );

    $disposals = ReversionFinder::disposalsToRevertOnAcquisition($acquisition, SharePoolingAssetTransactions::make());

    expect($disposals->isEmpty())->toBeTrue();
});
