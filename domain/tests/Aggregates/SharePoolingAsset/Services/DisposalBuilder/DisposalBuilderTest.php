<?php

use Brick\DateTime\LocalDate;
use Domain\Aggregates\SharePoolingAsset\Actions\DisposeOfSharePoolingAsset;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetAcquisition;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetTransactions;
use Domain\Aggregates\SharePoolingAsset\Services\DisposalBuilder\DisposalBuilder;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\ValueObjects\Asset;
use Domain\ValueObjects\FiatAmount;
use Domain\ValueObjects\Quantity;

it('does not process any acquisitions when the disposed of quantity is zero', function () {
    $action = new DisposeOfSharePoolingAsset(
        transactionId: $id = SharePoolingAssetTransactionId::fromString('foo'),
        asset: new Asset('FOO'),
        date: LocalDate::parse('2015-10-21'),
        quantity: Quantity::zero(),
        proceeds: FiatAmount::GBP('0'),
        forFiat: false,
    );

    $disposal = DisposalBuilder::make(
        disposal: $action,
        transactions: SharePoolingAssetTransactions::make(SharePoolingAssetAcquisition::factory()->make()),
    );

    expect($disposal->id)->toBe($id);
});

it('does not process any section 104 pool acquisitions when none were made before or on the day of the disposal', function () {
    $action = new DisposeOfSharePoolingAsset(
        transactionId: $id = SharePoolingAssetTransactionId::fromString('foo'),
        asset: new Asset('FOO'),
        date: LocalDate::parse('2015-10-21'),
        quantity: new Quantity('100'),
        proceeds: FiatAmount::GBP('0'),
        forFiat: false,
    );

    $acquisition = SharePoolingAssetAcquisition::factory()->make(['date' => LocalDate::parse('2021-10-22')]);

    $disposal = DisposalBuilder::make($action, SharePoolingAssetTransactions::make($acquisition));

    expect($disposal->id)->toBe($id);
});
