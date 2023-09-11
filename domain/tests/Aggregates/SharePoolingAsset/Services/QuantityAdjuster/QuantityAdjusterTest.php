<?php

use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetDisposal;
use Domain\Aggregates\SharePoolingAsset\Entities\SharePoolingAssetTransactions;
use Domain\Aggregates\SharePoolingAsset\Services\QuantityAdjuster\Exceptions\QuantityAdjusterException;
use Domain\Aggregates\SharePoolingAsset\Services\QuantityAdjuster\QuantityAdjuster;
use Domain\Aggregates\SharePoolingAsset\ValueObjects\SharePoolingAssetTransactionId;
use Domain\ValueObjects\Quantity;

it('cannot revert a disposal because an acquisition cannot be found', function () {
    $disposal = SharePoolingAssetDisposal::factory()
        ->withSameDayQuantity(Quantity::zero(), $id = SharePoolingAssetTransactionId::fromString('foo'))
        ->make();

    expect(fn () => QuantityAdjuster::revertDisposal($disposal, SharePoolingAssetTransactions::make()))
        ->toThrow(QuantityAdjusterException::class, QuantityAdjusterException::transactionNotFound($id)->getMessage());
});

it('cannot revert a disposal because a transaction is not an acquisition', function () {
    $disposal = SharePoolingAssetDisposal::factory()
        ->withSameDayQuantity(Quantity::zero(), $id = SharePoolingAssetTransactionId::fromString('foo'))
        ->make();

    $transactions = SharePoolingAssetTransactions::make(SharePoolingAssetDisposal::factory()->make(['id' => $id]));

    expect(fn () => QuantityAdjuster::revertDisposal($disposal, $transactions))
        ->toThrow(QuantityAdjusterException::class, QuantityAdjusterException::notAnAcquisition($id)->getMessage());
});
